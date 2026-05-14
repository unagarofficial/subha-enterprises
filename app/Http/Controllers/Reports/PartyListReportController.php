<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Exports\GenericExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PartyListReportController extends Controller
{
    public function list(Request $request)
    {
        $branches   = Branch::orderBy('br_name')->get();
        $states     = DB::table('parties')->distinct()->orderBy('state')->pluck('state')->filter()->values();
        $partyType  = $request->get('party_type', '');
        $state      = $request->get('state');
        $brCode     = $request->get('br_code', session('br_code'));
        $showReport = $request->has('show');

        $parties = collect();
        if ($showReport) {
            $parties = $this->getParties($partyType, $state, $brCode);
        }

        return view('reports.parties.list', compact(
            'branches', 'states', 'partyType', 'state', 'brCode',
            'showReport', 'parties'
        ));
    }

    public function listExport(Request $request)
    {
        $partyType = $request->get('party_type', '');
        $state     = $request->get('state');
        $brCode    = $request->get('br_code');

        $parties = $this->getParties($partyType, $state, $brCode);

        $headings = ['S.No', 'Party Code', 'Party Name', 'Address', 'Place', 'State', 'Mobile', 'GST No', 'Type'];
        $data = [];
        $sno  = 1;
        foreach ($parties as $p) {
            $data[] = [
                $sno++,
                $p->party_code,
                $p->party_name,
                $p->address,
                $p->place,
                $p->state,
                $p->mobile,
                $p->tin_grn_no,
                $p->party_type === 'C' ? 'Customer' : 'Supplier',
            ];
        }

        return Excel::download(new GenericExport($data, $headings), 'party-list.xlsx');
    }

    private function getParties($partyType, $state, $brCode)
    {
        return DB::table('parties')
            ->select(['party_code', 'party_name', 'party_type', 'address', 'place', 'state', 'mobile', 'tin_grn_no'])
            ->when($partyType, fn($q) => $q->where('party_type', $partyType))
            ->when($state,     fn($q) => $q->where('state', $state))
            ->when($brCode,    fn($q) => $q->where('br_code', $brCode))
            ->orderBy('party_name')
            ->get();
    }
}
