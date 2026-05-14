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

class LedgerReportController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // PARTY LEDGER
    // ──────────────────────────────────────────────────────────────────────────

    public function partyLedger(Request $request)
    {
        $branches   = Branch::orderBy('br_name')->get();
        $parties    = Party::orderBy('party_name')->get();
        $dateFrom   = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo     = $request->get('date_to',   now()->format('Y-m-d'));
        $brCode     = $request->get('br_code',   session('br_code'));
        $partyCode  = $request->get('party_code');
        $showReport = $request->has('show');

        $ledgerRows     = [];
        $openingBalance = 0;
        $party          = null;
        $error          = null;

        if ($showReport) {
            if (!$partyCode) {
                $error = 'Please select a party.';
            } elseif ($dateFrom > $dateTo) {
                $error = 'Date From must be ≤ Date To.';
            } else {
                $party          = Party::find($partyCode);
                $openingBalance = $this->getOpeningBalance($partyCode, $dateFrom, $brCode);
                $ledgerRows     = $this->getLedgerRows($partyCode, $dateFrom, $dateTo, $brCode);
            }
        }

        return view('reports.ledger.party', compact(
            'branches', 'parties', 'dateFrom', 'dateTo', 'brCode', 'partyCode',
            'showReport', 'ledgerRows', 'openingBalance', 'party', 'error'
        ));
    }

    public function partyLedgerExport(Request $request)
    {
        $dateFrom  = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo    = $request->get('date_to',   now()->format('Y-m-d'));
        $brCode    = $request->get('br_code');
        $partyCode = $request->get('party_code');

        $party          = Party::find($partyCode);
        $openingBalance = $this->getOpeningBalance($partyCode, $dateFrom, $brCode);
        $ledgerRows     = $this->getLedgerRows($partyCode, $dateFrom, $dateTo, $brCode);

        $headings = ['S.No', 'Date', 'Narration', 'Credit', 'Debit', 'Balance'];
        $data     = [];
        $balance  = $openingBalance;

        $data[] = ['', Carbon::parse($dateFrom)->format('d-M-Y'), 'Opening Balance',
            '', indianFmt(abs($openingBalance)), indianFmt($balance)];

        $sno = 1;
        foreach ($ledgerRows as $row) {
            $balance += $row->debit - $row->credit;
            $data[] = [
                $sno++,
                Carbon::parse($row->txn_date)->format('d-M-Y'),
                $row->narration,
                $row->credit > 0 ? indianFmt($row->credit) : '',
                $row->debit  > 0 ? indianFmt($row->debit)  : '',
                indianFmt($balance),
            ];
        }
        $data[] = ['', Carbon::parse($dateTo)->format('d-M-Y'), 'Closing Balance',
            '', '', indianFmt($balance)];

        $partyName = $party ? $party->party_name : 'party';

        return Excel::download(
            new GenericExport($data, $headings),
            'party-ledger-' . $partyName . '-' . $dateFrom . '-to-' . $dateTo . '.xlsx'
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function getOpeningBalance($partyCode, string $dateFrom, $brCode): float
    {
        $finYearId = session('fin_year_id');

        $sales = DB::table('sale_hdr')
            ->where('party_code', $partyCode)
            ->where('fin_year_id', $finYearId)
            ->where('inv_date', '<', $dateFrom)
            ->when($brCode, fn($q) => $q->where('br_code', $brCode))
            ->sum('nett');

        $saleRtns = DB::table('sale_rtn_hdr')
            ->where('party_code', $partyCode)
            ->where('fin_year_id', $finYearId)
            ->where('inv_date', '<', $dateFrom)
            ->when($brCode, fn($q) => $q->where('br_code', $brCode))
            ->sum('nett');

        $purchases = DB::table('purchase_hdr')
            ->where('party_code', $partyCode)
            ->where('fin_year_id', $finYearId)
            ->where('inv_date', '<', $dateFrom)
            ->when($brCode, fn($q) => $q->where('br_code', $brCode))
            ->sum('nett');

        $purchaseRtns = DB::table('purchase_rtn_hdr')
            ->where('party_code', $partyCode)
            ->where('fin_year_id', $finYearId)
            ->where('inv_date', '<', $dateFrom)
            ->when($brCode, fn($q) => $q->where('br_code', $brCode))
            ->sum('nett');

        // Debit = sales + purchase_returns; Credit = sale_returns + purchases
        return (float)$sales + (float)$purchaseRtns - (float)$saleRtns - (float)$purchases;
    }

    private function getLedgerRows($partyCode, string $dateFrom, string $dateTo, $brCode): array
    {
        $finYearId = session('fin_year_id');
        $rows      = [];

        // Sales → Debit (party owes us)
        $sales = DB::table('sale_hdr')
            ->where('party_code', $partyCode)
            ->where('fin_year_id', $finYearId)
            ->whereBetween('inv_date', [$dateFrom, $dateTo])
            ->when($brCode, fn($q) => $q->where('br_code', $brCode))
            ->orderBy('inv_date')->orderBy('inv_no')
            ->get(['inv_date', 'inv_no', 'nett']);

        foreach ($sales as $s) {
            $rows[] = (object)[
                'txn_date'  => $s->inv_date,
                'narration' => 'Invoice No: ' . $s->inv_no . ' Sale Bill',
                'credit'    => 0.0,
                'debit'     => (float)$s->nett,
            ];
        }

        // Sale Returns → Credit (we owe party back)
        $saleRtns = DB::table('sale_rtn_hdr')
            ->where('party_code', $partyCode)
            ->where('fin_year_id', $finYearId)
            ->whereBetween('inv_date', [$dateFrom, $dateTo])
            ->when($brCode, fn($q) => $q->where('br_code', $brCode))
            ->orderBy('inv_date')->orderBy('inv_no')
            ->get(['inv_date', 'inv_no', 'nett']);

        foreach ($saleRtns as $s) {
            $rows[] = (object)[
                'txn_date'  => $s->inv_date,
                'narration' => 'Return against Inv: ' . $s->inv_no,
                'credit'    => (float)$s->nett,
                'debit'     => 0.0,
            ];
        }

        // Purchases → Credit (we owe supplier)
        $purchases = DB::table('purchase_hdr')
            ->where('party_code', $partyCode)
            ->where('fin_year_id', $finYearId)
            ->whereBetween('inv_date', [$dateFrom, $dateTo])
            ->when($brCode, fn($q) => $q->where('br_code', $brCode))
            ->orderBy('inv_date')->orderBy('inv_no')
            ->get(['inv_date', 'inv_no', 'nett']);

        foreach ($purchases as $p) {
            $rows[] = (object)[
                'txn_date'  => $p->inv_date,
                'narration' => 'Invoice No: ' . $p->inv_no . ' Purchase',
                'credit'    => (float)$p->nett,
                'debit'     => 0.0,
            ];
        }

        // Purchase Returns → Debit (supplier owes us)
        $purchaseRtns = DB::table('purchase_rtn_hdr')
            ->where('party_code', $partyCode)
            ->where('fin_year_id', $finYearId)
            ->whereBetween('inv_date', [$dateFrom, $dateTo])
            ->when($brCode, fn($q) => $q->where('br_code', $brCode))
            ->orderBy('inv_date')->orderBy('inv_no')
            ->get(['inv_date', 'inv_no', 'nett']);

        foreach ($purchaseRtns as $p) {
            $rows[] = (object)[
                'txn_date'  => $p->inv_date,
                'narration' => 'Return Inv: ' . $p->inv_no,
                'credit'    => 0.0,
                'debit'     => (float)$p->nett,
            ];
        }

        usort($rows, fn($a, $b) => strcmp($a->txn_date, $b->txn_date));

        return $rows;
    }
}
