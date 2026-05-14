<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Exports\GenericExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ParcelReportController extends Controller
{
    public function list(Request $request)
    {
        $branches   = Branch::orderBy('br_name')->get();
        $dateFrom   = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo     = $request->get('date_to',   now()->format('Y-m-d'));
        $brCode     = $request->get('br_code',   session('br_code'));
        $showReport = $request->has('show');

        $dateGroups = [];
        $error      = null;

        if ($showReport) {
            if ($dateFrom > $dateTo) {
                $error = 'Date From must be ≤ Date To.';
            } else {
                $rows = $this->getParcelRows($dateFrom, $dateTo, $brCode);
                foreach ($rows as $row) {
                    $dateGroups[$row->inv_date][] = $row;
                }
            }
        }

        return view('reports.parcel.list', compact(
            'branches', 'dateFrom', 'dateTo', 'brCode',
            'showReport', 'dateGroups', 'error'
        ));
    }

    public function listExport(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->get('date_to',   now()->format('Y-m-d'));
        $brCode   = $request->get('br_code');

        $rows = $this->getParcelRows($dateFrom, $dateTo, $brCode);

        $headings = ['Date', 'Customer Name', 'Place', 'Items', 'Qty', 'Amount'];
        $data     = [];
        $dateGroups = [];
        foreach ($rows as $row) {
            $dateGroups[$row->inv_date][] = $row;
        }

        $grandQty    = 0;
        $grandAmount = 0;

        foreach ($dateGroups as $date => $parcels) {
            $dayQty    = 0;
            $dayAmount = 0;
            foreach ($parcels as $p) {
                $data[] = [
                    Carbon::parse($date)->format('d-M-Y'),
                    $p->party_name,
                    $p->place,
                    $p->item_count,
                    indianFmt($p->total_qty),
                    indianFmt($p->total_amount),
                ];
                $dayQty    += $p->total_qty;
                $dayAmount += $p->total_amount;
            }
            $data[] = ['Day Total — ' . Carbon::parse($date)->format('d-M-Y'), '', '', '', indianFmt($dayQty), indianFmt($dayAmount)];
            $grandQty    += $dayQty;
            $grandAmount += $dayAmount;
        }
        if (!empty($dateGroups)) {
            $data[] = ['Grand Total', '', '', '', indianFmt($grandQty), indianFmt($grandAmount)];
        }

        return Excel::download(
            new GenericExport($data, $headings),
            'parcel-list-' . $dateFrom . '-to-' . $dateTo . '.xlsx'
        );
    }

    private function getParcelRows(string $dateFrom, string $dateTo, $brCode)
    {
        // Join hdr→dtl to get qty and item count per date+party group
        return DB::table('sale_hdr as h')
            ->join('parties as p',  'h.party_code', '=', 'p.party_code')
            ->join('sale_dtl as d', function ($j) {
                $j->on('d.inv_no',  '=', 'h.inv_no')
                  ->on('d.br_code', '=', 'h.br_code');
            })
            ->selectRaw('
                h.inv_date,
                h.party_code,
                p.party_name,
                p.place,
                COUNT(DISTINCT d.id) as item_count,
                SUM(d.qty)           as total_qty,
                SUM(h.nett)          as total_amount
            ')
            ->whereBetween('h.inv_date', [$dateFrom, $dateTo])
            ->where('h.fin_year_id', session('fin_year_id'))
            ->when($brCode, fn($q) => $q->where('h.br_code', $brCode))
            ->groupBy('h.inv_date', 'h.party_code', 'p.party_name', 'p.place')
            ->orderBy('h.inv_date')
            ->orderBy('p.party_name')
            ->get();
    }
}
