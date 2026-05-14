<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Party;
use App\Models\Category;
use App\Exports\GenericExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class PurchaseReportController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // REPORT 1 — PURCHASE REGISTER
    // ──────────────────────────────────────────────────────────────────────────

    public function register(Request $request)
    {
        $branches   = Branch::orderBy('br_name')->get();
        $suppliers  = Party::suppliers()->orderBy('party_name')->get();
        $categories = Category::orderBy('cat_name')->get();
        $dateFrom   = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo     = $request->get('date_to',   now()->format('Y-m-d'));
        $brCode     = $request->get('br_code',   session('br_code'));
        $partyCode  = $request->get('party_code');
        $catCode    = $request->get('cat_code');
        $showReport = $request->has('show');

        $supplierGroups = [];
        $error = null;

        if ($showReport) {
            if ($dateFrom > $dateTo) {
                $error = 'Date From must be ≤ Date To.';
            } else {
                $rows = $this->getRegisterRows($dateFrom, $dateTo, $brCode, $partyCode, $catCode);
                foreach ($rows as $row) {
                    $supplierGroups[$row->party_code][] = $row;
                }
            }
        }

        return view('reports.purchase.register', compact(
            'branches', 'suppliers', 'categories',
            'dateFrom', 'dateTo', 'brCode', 'partyCode', 'catCode',
            'showReport', 'supplierGroups', 'error'
        ));
    }

    public function registerExport(Request $request)
    {
        $dateFrom  = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo    = $request->get('date_to',   now()->format('Y-m-d'));
        $brCode    = $request->get('br_code');
        $partyCode = $request->get('party_code');
        $catCode   = $request->get('cat_code');

        $rows = $this->getRegisterRows($dateFrom, $dateTo, $brCode, $partyCode, $catCode);

        $headings = ['Date', 'Inv No', 'Supplier', 'Place', 'Category', 'Item', 'Qty', 'UOM', 'Rate', 'Amount', 'Tax', 'Net'];
        $data     = [];

        $supplierGroups = [];
        foreach ($rows as $row) {
            $supplierGroups[$row->party_code][] = $row;
        }

        $grandAmt = $grandTax = $grandNet = 0.0;
        foreach ($supplierGroups as $items) {
            $first = $items[0];
            $sAmt = $sTax = $sNet = 0.0;
            foreach ($items as $item) {
                $data[] = [
                    Carbon::parse($item->inv_date)->format('d-M-Y'),
                    $item->inv_no, $item->party_name, $item->place,
                    $item->cat_name, $item->mat_name,
                    $item->qty, $item->uom_name,
                    indianFmt($item->rate), indianFmt($item->amount),
                    indianFmt($item->line_tax), indianFmt($item->line_net),
                ];
                $sAmt += $item->amount;
                $sTax += $item->line_tax;
                $sNet += $item->line_net;
            }
            $data[] = ['Supplier Total: ' . $first->party_name, '', '', '', '', '', '', '', '',
                indianFmt($sAmt), indianFmt($sTax), indianFmt($sNet)];
            $grandAmt += $sAmt;
            $grandTax += $sTax;
            $grandNet += $sNet;
        }
        if (!empty($supplierGroups)) {
            $data[] = ['Grand Total', '', '', '', '', '', '', '', '',
                indianFmt($grandAmt), indianFmt($grandTax), indianFmt($grandNet)];
        }

        return Excel::download(
            new GenericExport($data, $headings),
            'purchase-register-' . $dateFrom . '-to-' . $dateTo . '.xlsx'
        );
    }

    private function getRegisterRows(string $dateFrom, string $dateTo, $brCode, $partyCode, $catCode)
    {
        return DB::table('purchase_hdr as h')
            ->join('parties as p',      'h.party_code', '=', 'p.party_code')
            ->join('purchase_dtl as d', function ($j) {
                $j->on('d.br_code', '=', 'h.br_code')->on('d.inv_no', '=', 'h.inv_no');
            })
            ->join('products as pr',    'd.mat_code', '=', 'pr.mat_code')
            ->join('categories as c',   'd.cat_code', '=', 'c.cat_code')
            ->join('uoms as u',         'd.uom',      '=', 'u.uom_code')
            ->selectRaw('
                h.inv_date, h.inv_no, h.br_code, h.party_code, h.tax_rate,
                p.party_name, p.place,
                c.cat_name, pr.mat_name,
                d.qty, u.uom_name, d.rate, d.amount,
                ROUND(d.amount * h.tax_rate / 100, 2) as line_tax,
                ROUND(d.amount + d.amount * h.tax_rate / 100, 2) as line_net
            ')
            ->whereBetween('h.inv_date', [$dateFrom, $dateTo])
            ->where('h.fin_year_id', session('fin_year_id'))
            ->when($brCode,    fn($q) => $q->where('h.br_code',    $brCode))
            ->when($partyCode, fn($q) => $q->where('h.party_code', $partyCode))
            ->when($catCode,   fn($q) => $q->where('d.cat_code',   $catCode))
            ->orderBy('p.party_name')
            ->orderBy('h.inv_date')
            ->orderBy('h.inv_no')
            ->orderBy('d.sl_no')
            ->get();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // REPORT 2 — WEEKLY PURCHASE REPORT
    // ──────────────────────────────────────────────────────────────────────────

    public function weekly(Request $request)
    {
        $branches   = Branch::orderBy('br_name')->get();
        $weekFrom   = $request->get('week_from', now()->startOfWeek()->format('Y-m-d'));
        $weekTo     = $request->get('week_to',   Carbon::parse($weekFrom)->addDays(6)->format('Y-m-d'));
        $brCode     = $request->get('br_code',   session('br_code'));
        $showReport = $request->has('show');

        $weekRows = collect();
        $error    = null;

        if ($showReport) {
            if ($weekFrom > $weekTo) {
                $error = 'Week Start must be ≤ Week End.';
            } else {
                $weekRows = $this->getWeeklyRows($weekFrom, $weekTo, $brCode);
            }
        }

        return view('reports.purchase.weekly', compact(
            'branches', 'weekFrom', 'weekTo', 'brCode',
            'showReport', 'weekRows', 'error'
        ));
    }

    public function weeklyExport(Request $request)
    {
        $weekFrom = $request->get('week_from', now()->startOfWeek()->format('Y-m-d'));
        $weekTo   = $request->get('week_to',   Carbon::parse($weekFrom)->addDays(6)->format('Y-m-d'));
        $brCode   = $request->get('br_code');

        $rows = $this->getWeeklyRows($weekFrom, $weekTo, $brCode);

        $headings = ['Day', 'Date', 'Invoices', 'Gross', 'Tax Amt', 'Net'];
        $data     = [];
        $tInv = $tGross = $tTax = $tNet = 0;

        foreach ($rows as $row) {
            $data[] = [
                Carbon::parse($row->inv_date)->format('l'),
                Carbon::parse($row->inv_date)->format('d-M-Y'),
                $row->invoice_count,
                indianFmt($row->total_gross),
                indianFmt($row->total_tax),
                indianFmt($row->total_nett),
            ];
            $tInv   += $row->invoice_count;
            $tGross += $row->total_gross;
            $tTax   += $row->total_tax;
            $tNet   += $row->total_nett;
        }
        if ($rows->isNotEmpty()) {
            $data[] = ['Week Total', '', $tInv, indianFmt($tGross), indianFmt($tTax), indianFmt($tNet)];
        }

        return Excel::download(
            new GenericExport($data, $headings),
            'weekly-purchase-' . $weekFrom . '-to-' . $weekTo . '.xlsx'
        );
    }

    private function getWeeklyRows(string $weekFrom, string $weekTo, $brCode)
    {
        return DB::table('purchase_hdr as h')
            ->selectRaw('
                h.inv_date,
                COUNT(DISTINCT h.inv_no) as invoice_count,
                SUM(h.gross)             as total_gross,
                SUM(h.tax_amount)        as total_tax,
                SUM(h.nett)              as total_nett
            ')
            ->whereBetween('h.inv_date', [$weekFrom, $weekTo])
            ->where('h.fin_year_id', session('fin_year_id'))
            ->when($brCode, fn($q) => $q->where('h.br_code', $brCode))
            ->groupBy('h.inv_date')
            ->orderBy('h.inv_date')
            ->get();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // REPORT 3 — PURCHASE RETURN REPORT
    // ──────────────────────────────────────────────────────────────────────────

    public function returns(Request $request)
    {
        $branches   = Branch::orderBy('br_name')->get();
        $suppliers  = Party::suppliers()->orderBy('party_name')->get();
        $dateFrom   = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo     = $request->get('date_to',   now()->format('Y-m-d'));
        $brCode     = $request->get('br_code',   session('br_code'));
        $partyCode  = $request->get('party_code');
        $rtnType    = $request->get('rtn_type',  '');
        $showReport = $request->has('show');

        $supplierGroups = [];
        $error = null;

        if ($showReport) {
            if ($dateFrom > $dateTo) {
                $error = 'Date From must be ≤ Date To.';
            } else {
                $rows = $this->getReturnsRows($dateFrom, $dateTo, $brCode, $partyCode, $rtnType);
                foreach ($rows as $row) {
                    $supplierGroups[$row->party_code][] = $row;
                }
            }
        }

        return view('reports.purchase.returns', compact(
            'branches', 'suppliers',
            'dateFrom', 'dateTo', 'brCode', 'partyCode', 'rtnType',
            'showReport', 'supplierGroups', 'error'
        ));
    }

    public function returnsExport(Request $request)
    {
        $dateFrom  = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo    = $request->get('date_to',   now()->format('Y-m-d'));
        $brCode    = $request->get('br_code');
        $partyCode = $request->get('party_code');
        $rtnType   = $request->get('rtn_type', '');

        $rows = $this->getReturnsRows($dateFrom, $dateTo, $brCode, $partyCode, $rtnType);

        $headings = ['Date', 'Ret No', 'Supplier', 'Place', 'Category', 'Item', 'Qty', 'UOM', 'Rate', 'Amount', 'Tax', 'Net'];
        $data     = [];

        $supplierGroups = [];
        foreach ($rows as $row) {
            $supplierGroups[$row->party_code][] = $row;
        }

        $grandAmt = $grandTax = $grandNet = 0.0;
        foreach ($supplierGroups as $items) {
            $first = $items[0];
            $sAmt = $sTax = $sNet = 0.0;
            foreach ($items as $item) {
                $data[] = [
                    Carbon::parse($item->inv_date)->format('d-M-Y'),
                    $item->inv_no, $item->party_name, $item->place,
                    $item->cat_name, $item->mat_name,
                    $item->qty, $item->uom_name,
                    indianFmt($item->rate), indianFmt($item->amount),
                    indianFmt($item->line_tax), indianFmt($item->line_net),
                ];
                $sAmt += $item->amount;
                $sTax += $item->line_tax;
                $sNet += $item->line_net;
            }
            $data[] = ['Supplier Total: ' . $first->party_name, '', '', '', '', '', '', '', '',
                indianFmt($sAmt), indianFmt($sTax), indianFmt($sNet)];
            $grandAmt += $sAmt;
            $grandTax += $sTax;
            $grandNet += $sNet;
        }
        if (!empty($supplierGroups)) {
            $data[] = ['Grand Total', '', '', '', '', '', '', '', '',
                indianFmt($grandAmt), indianFmt($grandTax), indianFmt($grandNet)];
        }

        return Excel::download(
            new GenericExport($data, $headings),
            'purchase-returns-' . $dateFrom . '-to-' . $dateTo . '.xlsx'
        );
    }

    private function getReturnsRows(string $dateFrom, string $dateTo, $brCode, $partyCode, $rtnType)
    {
        return DB::table('purchase_rtn_hdr as h')
            ->join('parties as p',          'h.party_code', '=', 'p.party_code')
            ->join('purchase_rtn_dtl as d',  function ($j) {
                $j->on('d.br_code', '=', 'h.br_code')->on('d.inv_no', '=', 'h.inv_no');
            })
            ->join('products as pr',    'd.mat_code',  '=', 'pr.mat_code')
            ->join('categories as c',   'pr.cat_code', '=', 'c.cat_code')
            ->join('uoms as u',         'd.uom',       '=', 'u.uom_code')
            ->selectRaw('
                h.inv_date, h.inv_no, h.br_code, h.party_code, h.tax_rate, h.rtn_type,
                p.party_name, p.place,
                c.cat_name, pr.mat_name,
                d.qty, u.uom_name, d.rate, d.amount,
                ROUND(d.amount * h.tax_rate / 100, 2) as line_tax,
                ROUND(d.amount + d.amount * h.tax_rate / 100, 2) as line_net
            ')
            ->whereBetween('h.inv_date', [$dateFrom, $dateTo])
            ->where('h.fin_year_id', session('fin_year_id'))
            ->when($brCode,    fn($q) => $q->where('h.br_code',    $brCode))
            ->when($partyCode, fn($q) => $q->where('h.party_code', $partyCode))
            ->when($rtnType,   fn($q) => $q->where('h.rtn_type',   $rtnType))
            ->orderBy('p.party_name')
            ->orderBy('h.inv_date')
            ->orderBy('h.inv_no')
            ->orderBy('d.sl_no')
            ->get();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // REPORT 4 — SELF PURCHASE REPORT
    // ──────────────────────────────────────────────────────────────────────────

    public function selfPurchase(Request $request)
    {
        $branches   = Branch::orderBy('br_name')->get();
        $suppliers  = Party::suppliers()->orderBy('party_name')->get();
        $dateFrom   = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo     = $request->get('date_to',   now()->format('Y-m-d'));
        $brCode     = $request->get('br_code',   session('br_code'));
        $partyCode  = $request->get('party_code');
        $printType  = $request->get('print_type', 'all');
        $showReport = $request->has('show');

        $supplierGroups = [];
        $error = null;

        if ($showReport) {
            if ($dateFrom > $dateTo) {
                $error = 'Date From must be ≤ Date To.';
            } else {
                $rows = $this->getSelfPurchaseRows($dateFrom, $dateTo, $brCode, $partyCode, $printType);
                foreach ($rows as $row) {
                    $supplierGroups[$row->party_code][] = $row;
                }
            }
        }

        return view('reports.purchase.self-purchase', compact(
            'branches', 'suppliers',
            'dateFrom', 'dateTo', 'brCode', 'partyCode', 'printType',
            'showReport', 'supplierGroups', 'error'
        ));
    }

    public function selfPurchaseExport(Request $request)
    {
        $dateFrom  = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo    = $request->get('date_to',   now()->format('Y-m-d'));
        $brCode    = $request->get('br_code');
        $partyCode = $request->get('party_code');
        $printType = $request->get('print_type', 'all');

        $rows = $this->getSelfPurchaseRows($dateFrom, $dateTo, $brCode, $partyCode, $printType);

        $typeLabel  = match($printType) { 'covering' => ' (Covering)', 'plating' => ' (Plating)', default => '' };
        $headings   = ['Date', 'Inv No', 'Supplier', 'Place', 'Category', 'Item', 'Qty', 'UOM', 'Rate', 'Amount', 'Tax', 'Net'];
        $data       = [];

        $supplierGroups = [];
        foreach ($rows as $row) {
            $supplierGroups[$row->party_code][] = $row;
        }

        $grandAmt = $grandTax = $grandNet = 0.0;
        foreach ($supplierGroups as $items) {
            $first = $items[0];
            $sAmt = $sTax = $sNet = 0.0;
            foreach ($items as $item) {
                $data[] = [
                    Carbon::parse($item->inv_date)->format('d-M-Y'),
                    $item->inv_no, $item->party_name, $item->place,
                    $item->cat_name, $item->mat_name,
                    $item->qty, $item->uom_name,
                    indianFmt($item->rate), indianFmt($item->amount),
                    indianFmt($item->line_tax), indianFmt($item->line_net),
                ];
                $sAmt += $item->amount;
                $sTax += $item->line_tax;
                $sNet += $item->line_net;
            }
            $data[] = ['Supplier Total: ' . $first->party_name, '', '', '', '', '', '', '', '',
                indianFmt($sAmt), indianFmt($sTax), indianFmt($sNet)];
            $grandAmt += $sAmt;
            $grandTax += $sTax;
            $grandNet += $sNet;
        }
        if (!empty($supplierGroups)) {
            $data[] = ['Grand Total', '', '', '', '', '', '', '', '',
                indianFmt($grandAmt), indianFmt($grandTax), indianFmt($grandNet)];
        }

        return Excel::download(
            new GenericExport($data, $headings),
            'self-purchase-' . $dateFrom . '-to-' . $dateTo . $typeLabel . '.xlsx'
        );
    }

    private function getSelfPurchaseRows(string $dateFrom, string $dateTo, $brCode, $partyCode, $printType)
    {
        return DB::table('purchase_hdr as h')
            ->join('parties as p',      'h.party_code', '=', 'p.party_code')
            ->join('purchase_dtl as d', function ($j) {
                $j->on('d.br_code', '=', 'h.br_code')->on('d.inv_no', '=', 'h.inv_no');
            })
            ->join('products as pr',    'd.mat_code', '=', 'pr.mat_code')
            ->join('categories as c',   'd.cat_code', '=', 'c.cat_code')
            ->join('uoms as u',         'd.uom',      '=', 'u.uom_code')
            ->selectRaw('
                h.inv_date, h.inv_no, h.br_code, h.party_code, h.tax_rate,
                p.party_name, p.place,
                c.cat_name, pr.mat_name,
                d.qty, u.uom_name, d.rate, d.amount,
                ROUND(d.amount * h.tax_rate / 100, 2) as line_tax,
                ROUND(d.amount + d.amount * h.tax_rate / 100, 2) as line_net
            ')
            ->whereBetween('h.inv_date', [$dateFrom, $dateTo])
            ->where('h.fin_year_id', session('fin_year_id'))
            ->when($brCode,                    fn($q) => $q->where('h.br_code',    $brCode))
            ->when($partyCode,                 fn($q) => $q->where('h.party_code', $partyCode))
            ->when($printType === 'covering',  fn($q) => $q->where('c.cat_name', 'LIKE', '%Covering%'))
            ->when($printType === 'plating',   fn($q) => $q->where('c.cat_name', 'LIKE', '%Plating%'))
            ->orderBy('p.party_name')
            ->orderBy('h.inv_date')
            ->orderBy('h.inv_no')
            ->orderBy('d.sl_no')
            ->get();
    }
}
