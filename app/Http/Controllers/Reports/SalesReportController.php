<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Party;
use App\Exports\GenericExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class SalesReportController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // REPORT 1 — DAILY SALES REPORT
    // ──────────────────────────────────────────────────────────────────────────

    public function daily(Request $request)
    {
        $branches  = Branch::orderBy('br_name')->get();
        $dateFrom  = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo    = $request->get('date_to',   now()->format('Y-m-d'));
        $brCode    = $request->get('br_code',   session('br_code'));
        $showReport = $request->has('show');
        $includeTax = $showReport ? $request->has('include_tax') : true;

        $dateGroups = [];
        $error = null;

        if ($showReport) {
            if ($dateFrom > $dateTo) {
                $error = 'Date From must be ≤ Date To.';
            } else {
                $rows = $this->getDailyRows($dateFrom, $dateTo, $brCode);
                foreach ($rows as $row) {
                    $dateGroups[$row->inv_date][] = $row;
                }
            }
        }

        return view('reports.sales.daily', compact(
            'branches', 'dateFrom', 'dateTo', 'brCode', 'includeTax',
            'showReport', 'dateGroups', 'error'
        ));
    }

    public function dailyExport(Request $request)
    {
        $dateFrom   = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo     = $request->get('date_to',   now()->format('Y-m-d'));
        $brCode     = $request->get('br_code');
        $includeTax = (bool) $request->get('include_tax', 1);

        $rows = $this->getDailyRows($dateFrom, $dateTo, $brCode);

        $headings = ['Date', 'Invoice No', 'Customer', 'Place', 'Items', 'Gross'];
        if ($includeTax) {
            $headings[] = 'Tax%';
            $headings[] = 'Tax Amt';
        }
        $headings[] = 'Net';

        $data       = [];
        $prevDate   = null;
        $dayGross   = $dayTax = $dayNet = 0;
        $grandGross = $grandTax = $grandNet = 0;

        foreach ($rows as $row) {
            if ($prevDate !== null && $prevDate !== $row->inv_date) {
                $data[] = $this->dayTotalRow($prevDate, $dayGross, $dayTax, $dayNet, $includeTax);
                $dayGross = $dayTax = $dayNet = 0;
            }
            $prevDate = $row->inv_date;
            $line = [
                Carbon::parse($row->inv_date)->format('d-M-Y'),
                $row->inv_no, $row->party_name, $row->place, $row->item_count,
                indianFmt($row->gross),
            ];
            if ($includeTax) {
                $line[] = $row->tax_rate . '%';
                $line[] = indianFmt($row->tax_amount);
            }
            $line[] = indianFmt($row->nett);
            $data[] = $line;

            $dayGross   += $row->gross;   $dayTax   += $row->tax_amount; $dayNet   += $row->nett;
            $grandGross += $row->gross;   $grandTax += $row->tax_amount; $grandNet += $row->nett;
        }
        if ($prevDate !== null) {
            $data[] = $this->dayTotalRow($prevDate, $dayGross, $dayTax, $dayNet, $includeTax);
        }
        if (!empty($data)) {
            $data[] = $this->grandTotalRow('Grand Total', $grandGross, $grandTax, $grandNet, $includeTax);
        }

        return Excel::download(
            new GenericExport($data, $headings),
            'daily-sales-' . $dateFrom . '-to-' . $dateTo . '.xlsx'
        );
    }

    private function getDailyRows(string $dateFrom, string $dateTo, $brCode)
    {
        return DB::table('sale_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->select([
                'h.inv_date', 'h.inv_no', 'h.br_code',
                'p.party_name', 'p.place',
                'h.gross', 'h.tax_rate', 'h.tax_amount', 'h.nett',
                DB::raw('(SELECT COUNT(*) FROM sale_dtl d WHERE d.inv_no = h.inv_no AND d.br_code = h.br_code) as item_count'),
            ])
            ->whereBetween('h.inv_date', [$dateFrom, $dateTo])
            ->where('h.fin_year_id', session('fin_year_id'))
            ->when($brCode, fn($q) => $q->where('h.br_code', $brCode))
            ->orderBy('h.inv_date')
            ->orderBy('h.inv_no')
            ->get();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // REPORT 2 — PARTY-WISE / AREA SALES REPORT
    // ──────────────────────────────────────────────────────────────────────────

    public function partyWise(Request $request)
    {
        $branches   = Branch::orderBy('br_name')->get();
        $customers  = Party::customers()->orderBy('party_name')->get();
        $dateFrom   = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo     = $request->get('date_to',   now()->format('Y-m-d'));
        $brCode     = $request->get('br_code',   session('br_code'));
        $partyCode  = $request->get('party_code');
        $showReport = $request->has('show');

        $partyGroups = [];
        $error = null;

        if ($showReport) {
            if ($dateFrom > $dateTo) {
                $error = 'Date From must be ≤ Date To.';
            } else {
                $rows = $this->getPartyWiseRows($dateFrom, $dateTo, $brCode, $partyCode);
                foreach ($rows as $row) {
                    $partyGroups[$row->party_code][] = $row;
                }
            }
        }

        return view('reports.sales.party-wise', compact(
            'branches', 'customers', 'dateFrom', 'dateTo', 'brCode', 'partyCode',
            'showReport', 'partyGroups', 'error'
        ));
    }

    public function partyWiseExport(Request $request)
    {
        $dateFrom  = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo    = $request->get('date_to',   now()->format('Y-m-d'));
        $brCode    = $request->get('br_code');
        $partyCode = $request->get('party_code');

        $rows = $this->getPartyWiseRows($dateFrom, $dateTo, $brCode, $partyCode);

        $headings   = ['Customer', 'Place', 'State', 'Date', 'Invoice No', 'Items', 'Gross', 'Tax', 'Net'];
        $data       = [];
        $partyGroups = [];
        foreach ($rows as $row) {
            $partyGroups[$row->party_code][] = $row;
        }

        $grandGross = $grandTax = $grandNet = 0;
        foreach ($partyGroups as $invoices) {
            $first = $invoices[0];
            $cGross = $cTax = $cNet = 0;
            foreach ($invoices as $inv) {
                $data[] = [
                    $first->party_name, $first->place, $first->state,
                    Carbon::parse($inv->inv_date)->format('d-M-Y'),
                    $inv->inv_no, $inv->item_count,
                    indianFmt($inv->gross), indianFmt($inv->tax_amount), indianFmt($inv->nett),
                ];
                $cGross += $inv->gross; $cTax += $inv->tax_amount; $cNet += $inv->nett;
            }
            $data[] = ['Customer Total: ' . $first->party_name, '', '', '', '', '',
                indianFmt($cGross), indianFmt($cTax), indianFmt($cNet)];
            $grandGross += $cGross; $grandTax += $cTax; $grandNet += $cNet;
        }
        if (!empty($partyGroups)) {
            $data[] = ['Grand Total', '', '', '', '', '',
                indianFmt($grandGross), indianFmt($grandTax), indianFmt($grandNet)];
        }

        return Excel::download(
            new GenericExport($data, $headings),
            'party-wise-sales-' . $dateFrom . '-to-' . $dateTo . '.xlsx'
        );
    }

    private function getPartyWiseRows(string $dateFrom, string $dateTo, $brCode, $partyCode)
    {
        return DB::table('sale_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->select([
                'h.inv_date', 'h.inv_no', 'h.br_code',
                'h.party_code', 'p.party_name', 'p.place', 'p.state',
                'h.gross', 'h.tax_rate', 'h.tax_amount', 'h.nett',
                DB::raw('(SELECT COUNT(*) FROM sale_dtl d WHERE d.inv_no = h.inv_no AND d.br_code = h.br_code) as item_count'),
            ])
            ->whereBetween('h.inv_date', [$dateFrom, $dateTo])
            ->where('h.fin_year_id', session('fin_year_id'))
            ->when($brCode,    fn($q) => $q->where('h.br_code',    $brCode))
            ->when($partyCode, fn($q) => $q->where('h.party_code', $partyCode))
            ->orderBy('p.party_name')
            ->orderBy('h.inv_date')
            ->orderBy('h.inv_no')
            ->get();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // REPORT 3 — WEEKLY SALES REPORT
    // ──────────────────────────────────────────────────────────────────────────

    public function weekly(Request $request)
    {
        $branches  = Branch::orderBy('br_name')->get();
        $weekFrom  = $request->get('week_from', now()->startOfWeek()->format('Y-m-d'));
        $weekTo    = $request->get('week_to',   Carbon::parse($weekFrom)->addDays(6)->format('Y-m-d'));
        $brCode    = $request->get('br_code',   session('br_code'));
        $showReport = $request->has('show');

        $weekRows = collect();
        $error = null;

        if ($showReport) {
            if ($weekFrom > $weekTo) {
                $error = 'Week Start must be ≤ Week End.';
            } else {
                $weekRows = $this->getWeeklyRows($weekFrom, $weekTo, $brCode);
            }
        }

        return view('reports.sales.weekly', compact(
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
            $tInv += $row->invoice_count; $tGross += $row->total_gross;
            $tTax += $row->total_tax;     $tNet   += $row->total_nett;
        }
        if ($rows->isNotEmpty()) {
            $data[] = ['Week Total', '', $tInv, indianFmt($tGross), indianFmt($tTax), indianFmt($tNet)];
        }

        return Excel::download(
            new GenericExport($data, $headings),
            'weekly-sales-' . $weekFrom . '-to-' . $weekTo . '.xlsx'
        );
    }

    private function getWeeklyRows(string $weekFrom, string $weekTo, $brCode)
    {
        return DB::table('sale_hdr as h')
            ->selectRaw('h.inv_date,
                COUNT(DISTINCT h.inv_no, h.br_code) as invoice_count,
                SUM(h.gross)       as total_gross,
                SUM(h.tax_amount)  as total_tax,
                SUM(h.nett)        as total_nett')
            ->whereBetween('h.inv_date', [$weekFrom, $weekTo])
            ->where('h.fin_year_id', session('fin_year_id'))
            ->when($brCode, fn($q) => $q->where('h.br_code', $brCode))
            ->groupBy('h.inv_date')
            ->orderBy('h.inv_date')
            ->get();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // REPORT 4 — CUSTOMER-WISE WEEKLY SALE
    // ──────────────────────────────────────────────────────────────────────────

    public function customerWeekly(Request $request)
    {
        $branches  = Branch::orderBy('br_name')->get();
        $weekFrom  = $request->get('week_from', now()->startOfWeek()->format('Y-m-d'));
        $weekTo    = $request->get('week_to',   Carbon::parse($weekFrom)->addDays(6)->format('Y-m-d'));
        $brCode    = $request->get('br_code',   session('br_code'));
        $showReport = $request->has('show');

        $dates     = [];
        $pivotData = [];
        $colTotals = [];
        $error = null;

        if ($showReport) {
            if ($weekFrom > $weekTo) {
                $error = 'Week From must be ≤ Week To.';
            } else {
                [$dates, $pivotData, $colTotals] = $this->getCustomerWeeklyData($weekFrom, $weekTo, $brCode);
            }
        }

        return view('reports.sales.customer-weekly', compact(
            'branches', 'weekFrom', 'weekTo', 'brCode',
            'showReport', 'dates', 'pivotData', 'colTotals', 'error'
        ));
    }

    public function customerWeeklyExport(Request $request)
    {
        $weekFrom = $request->get('week_from', now()->startOfWeek()->format('Y-m-d'));
        $weekTo   = $request->get('week_to',   Carbon::parse($weekFrom)->addDays(6)->format('Y-m-d'));
        $brCode   = $request->get('br_code');

        [$dates, $pivotData, $colTotals] = $this->getCustomerWeeklyData($weekFrom, $weekTo, $brCode);

        $headings = ['Customer'];
        foreach ($dates as $d) {
            $headings[] = Carbon::parse($d)->format('D d/M');
        }
        $headings[] = 'Total';

        $data = [];
        foreach ($pivotData as $cust) {
            $row = [$cust['name']];
            foreach ($dates as $d) {
                $row[] = $cust['days'][$d] ?? 0;
            }
            $row[] = indianFmt($cust['total']);
            $data[] = $row;
        }
        if (!empty($pivotData)) {
            $totalRow = ['Column Total'];
            $grand    = 0;
            foreach ($dates as $d) {
                $totalRow[] = indianFmt($colTotals[$d] ?? 0);
                $grand      += ($colTotals[$d] ?? 0);
            }
            $totalRow[] = indianFmt($grand);
            $data[]     = $totalRow;
        }

        return Excel::download(
            new GenericExport($data, $headings),
            'customer-weekly-sales-' . $weekFrom . '-to-' . $weekTo . '.xlsx'
        );
    }

    private function getCustomerWeeklyData(string $weekFrom, string $weekTo, $brCode): array
    {
        $rows = DB::table('sale_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->selectRaw('h.party_code, p.party_name, h.inv_date, SUM(h.nett) as day_amount')
            ->whereBetween('h.inv_date', [$weekFrom, $weekTo])
            ->where('h.fin_year_id', session('fin_year_id'))
            ->when($brCode, fn($q) => $q->where('h.br_code', $brCode))
            ->groupBy('h.party_code', 'p.party_name', 'h.inv_date')
            ->orderBy('p.party_name')
            ->orderBy('h.inv_date')
            ->get();

        // All dates in the range
        $dates   = [];
        $current = Carbon::parse($weekFrom);
        $end     = Carbon::parse($weekTo);
        while ($current->lte($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        $pivotData = [];
        $colTotals = array_fill_keys($dates, 0.0);

        foreach ($rows as $row) {
            if (!isset($pivotData[$row->party_code])) {
                $pivotData[$row->party_code] = [
                    'name'  => $row->party_name,
                    'days'  => array_fill_keys($dates, 0.0),
                    'total' => 0.0,
                ];
            }
            $pivotData[$row->party_code]['days'][$row->inv_date]  = (float) $row->day_amount;
            $pivotData[$row->party_code]['total']                 += (float) $row->day_amount;
            $colTotals[$row->inv_date]                           += (float) $row->day_amount;
        }

        return [$dates, array_values($pivotData), $colTotals];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function dayTotalRow(string $date, float $gross, float $tax, float $net, bool $includeTax): array
    {
        $label = 'Day Total (' . Carbon::parse($date)->format('d-M-Y') . ')';
        $row   = [$label, '', '', '', '', indianFmt($gross)];
        if ($includeTax) {
            $row[] = '';
            $row[] = indianFmt($tax);
        }
        $row[] = indianFmt($net);
        return $row;
    }

    private function grandTotalRow(string $label, float $gross, float $tax, float $net, bool $includeTax): array
    {
        $row = [$label, '', '', '', '', indianFmt($gross)];
        if ($includeTax) {
            $row[] = '';
            $row[] = indianFmt($tax);
        }
        $row[] = indianFmt($net);
        return $row;
    }
}
