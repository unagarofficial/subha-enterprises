<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Firm;
use App\Models\Party;
use App\Models\Product;
use App\Models\Tax;
use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstimationController extends Controller
{
    // ── List ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $dateFrom  = $request->get('date_from', session('login_date'));
        $dateTo    = $request->get('date_to', session('login_date'));
        $partyCode = $request->get('party_code', '');
        $brCode    = $request->get('br_code', session('br_code'));

        $query = DB::table('prtn_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->join('branches as b', 'h.br_code', '=', 'b.br_code')
            ->where('h.fin_year_id', session('fin_year_id'))
            ->where('h.br_code', $brCode)
            ->whereBetween('h.inv_date', [$dateFrom, $dateTo]);

        if ($partyCode) {
            $query->where('h.party_code', $partyCode);
        }

        $estimations = $query
            ->orderBy('h.inv_date', 'desc')
            ->orderBy('h.inv_no', 'desc')
            ->select('h.*', 'p.party_name', 'p.place as party_place', 'b.br_name')
            ->get();

        $parties  = Party::orderBy('party_name')->get();
        $branches = Branch::orderBy('br_name')->get();

        return view('transactions.estimation.index', compact(
            'estimations', 'parties', 'branches',
            'dateFrom', 'dateTo', 'partyCode', 'brCode'
        ));
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function create()
    {
        $brCode   = (int) session('br_code');
        $nextInvNo = $this->nextInvNo($brCode, (int) session('fin_year_id'));
        $hdr      = null;
        $dtls     = collect();
        $firm     = Firm::first();

        [$branches, $parties, $taxes, $uoms, $products] = $this->formData($brCode);

        return view('transactions.estimation.form', compact(
            'hdr', 'dtls', 'branches', 'parties', 'taxes', 'uoms', 'products',
            'nextInvNo', 'firm'
        ));
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->validateEstimation($request);

        DB::transaction(function () use ($request) {
            $brCode    = (int) $request->br_code;
            $finYearId = (int) session('fin_year_id');
            $invNo     = $this->nextInvNo($brCode, $finYearId);
            $firm      = Firm::first();
            $now       = now();

            $taxRate   = (float) ($request->tax_rate ?? 0);
            $gross     = $this->calcGross($request->items);
            $taxAmount = round($gross * $taxRate / 100, 2);
            $nett      = round($gross + $taxAmount, 2);

            DB::table('prtn_hdr')->insert([
                'ho_code'     => $firm?->ho_code,
                'br_code'     => $brCode,
                'inv_no'      => $invNo,
                'inv_date'    => $request->inv_date,
                'party_code'  => (int) $request->party_code,
                'gross'       => $gross,
                'tax_rate'    => $taxRate,
                'tax_amount'  => $taxAmount,
                'nett'        => $nett,
                'fin_year_id' => $finYearId,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $slNo = 1;
            foreach ($request->items as $row) {
                $qty    = (float) ($row['qty'] ?? 0);
                $rate   = (float) ($row['rate'] ?? 0);
                $sValue = round($qty * $rate, 2);

                DB::table('prtn_dtl')->insert([
                    'br_code'    => $brCode,
                    'inv_no'     => $invNo,
                    'sl_no'      => $slNo++,
                    'mat_code'   => $row['mat_code'],
                    'qty'        => $qty,
                    'uom'        => (int) $row['uom'],
                    'rate'       => $rate,
                    's_value'    => $sValue,
                    'narration'  => $row['narration'] ?? null,
                    'inv_date'   => $request->inv_date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        return redirect()->route('transactions.estimation.index')
                         ->with('success', 'Estimation invoice saved successfully.');
    }

    // ── Edit ─────────────────────────────────────────────────────────────────

    public function edit($brCode, $invNo)
    {
        $brCode = (int) $brCode;
        $invNo  = (int) $invNo;

        $hdr = DB::table('prtn_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->where('h.br_code', $brCode)
            ->where('h.inv_no', $invNo)
            ->select('h.*', 'p.party_name', 'p.address as party_address', 'p.place as party_place')
            ->first();

        abort_if(!$hdr, 404);

        $dtls = DB::table('prtn_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->join('uoms as u', 'd.uom', '=', 'u.uom_code')
            ->where('d.br_code', $brCode)
            ->where('d.inv_no', $invNo)
            ->orderBy('d.sl_no')
            ->select('d.*', 'p.mat_name', 'p.sale_rate', 'u.uom_name')
            ->get();

        [$branches, $parties, $taxes, $uoms, $products] = $this->formData($brCode);
        $nextInvNo = $hdr->inv_no;
        $firm      = Firm::first();

        return view('transactions.estimation.form', compact(
            'hdr', 'dtls', 'branches', 'parties', 'taxes', 'uoms', 'products',
            'nextInvNo', 'firm'
        ));
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, $brCode, $invNo)
    {
        $brCode = (int) $brCode;
        $invNo  = (int) $invNo;

        abort_if(!DB::table('prtn_hdr')->where('br_code', $brCode)->where('inv_no', $invNo)->exists(), 404);

        $this->validateEstimation($request);

        DB::transaction(function () use ($request, $brCode, $invNo) {
            $now       = now();
            $taxRate   = (float) ($request->tax_rate ?? 0);
            $gross     = $this->calcGross($request->items);
            $taxAmount = round($gross * $taxRate / 100, 2);
            $nett      = round($gross + $taxAmount, 2);

            DB::table('prtn_hdr')
                ->where('br_code', $brCode)->where('inv_no', $invNo)
                ->update([
                    'inv_date'   => $request->inv_date,
                    'party_code' => (int) $request->party_code,
                    'gross'      => $gross,
                    'tax_rate'   => $taxRate,
                    'tax_amount' => $taxAmount,
                    'nett'       => $nett,
                    'updated_at' => $now,
                ]);

            DB::table('prtn_dtl')->where('br_code', $brCode)->where('inv_no', $invNo)->delete();

            $slNo = 1;
            foreach ($request->items as $row) {
                $qty    = (float) ($row['qty'] ?? 0);
                $rate   = (float) ($row['rate'] ?? 0);
                $sValue = round($qty * $rate, 2);

                DB::table('prtn_dtl')->insert([
                    'br_code'    => $brCode,
                    'inv_no'     => $invNo,
                    'sl_no'      => $slNo++,
                    'mat_code'   => $row['mat_code'],
                    'qty'        => $qty,
                    'uom'        => (int) $row['uom'],
                    'rate'       => $rate,
                    's_value'    => $sValue,
                    'narration'  => $row['narration'] ?? null,
                    'inv_date'   => $request->inv_date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        return redirect()->route('transactions.estimation.index')
                         ->with('success', 'Estimation invoice updated successfully.');
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function destroy($brCode, $invNo)
    {
        $brCode = (int) $brCode;
        $invNo  = (int) $invNo;

        abort_if(!DB::table('prtn_hdr')->where('br_code', $brCode)->where('inv_no', $invNo)->exists(), 404);

        DB::transaction(function () use ($brCode, $invNo) {
            DB::table('prtn_dtl')->where('br_code', $brCode)->where('inv_no', $invNo)->delete();
            DB::table('prtn_hdr')->where('br_code', $brCode)->where('inv_no', $invNo)->delete();
        });

        return back()->with('success', 'Estimation invoice deleted.');
    }

    // ── Print ─────────────────────────────────────────────────────────────────

    public function printInvoice($brCode, $invNo)
    {
        $brCode = (int) $brCode;
        $invNo  = (int) $invNo;

        $hdr = DB::table('prtn_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->join('branches as b', 'h.br_code', '=', 'b.br_code')
            ->where('h.br_code', $brCode)
            ->where('h.inv_no', $invNo)
            ->select('h.*', 'p.party_name', 'p.address as party_address',
                     'p.place as party_place', 'p.state as party_state',
                     'p.tin_grn_no', 'b.br_name', 'b.address as br_address',
                     'b.place as br_place')
            ->first();

        abort_if(!$hdr, 404);

        $dtls = DB::table('prtn_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->join('uoms as u', 'd.uom', '=', 'u.uom_code')
            ->where('d.br_code', $brCode)
            ->where('d.inv_no', $invNo)
            ->orderBy('d.sl_no')
            ->select('d.*', 'p.mat_name', 'u.uom_name')
            ->get();

        $firm = Firm::first();

        return view('transactions.estimation.print', compact('hdr', 'dtls', 'firm'));
    }

    // ── AJAX: get product rate ────────────────────────────────────────────────

    public function getProduct($matCode)
    {
        $product = DB::table('products as p')
            ->join('uoms as u', 'p.uom', '=', 'u.uom_code')
            ->where('p.mat_code', $matCode)
            ->select('p.mat_code', 'p.mat_name', 'p.sale_rate as rate', 'p.uom as uom_code', 'u.uom_name')
            ->first();

        abort_if(!$product, 404);
        return response()->json($product);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function formData(int $brCode): array
    {
        return [
            Branch::orderBy('br_name')->get(),
            Party::orderBy('party_name')->get(),
            Tax::orderBy('tax_rate')->get(),
            Uom::orderBy('uom_name')->get(),
            Product::with(['uomUnit'])->orderBy('mat_name')->get(),
        ];
    }

    private function nextInvNo(int $brCode, int $finYearId): int
    {
        return (int) (DB::table('prtn_hdr')
            ->where('br_code', $brCode)
            ->where('fin_year_id', $finYearId)
            ->max('inv_no') ?? 0) + 1;
    }

    private function calcGross(array $items): float
    {
        $gross = 0;
        foreach ($items as $row) {
            $gross += (float) ($row['qty'] ?? 0) * (float) ($row['rate'] ?? 0);
        }
        return round($gross, 2);
    }

    private function validateEstimation(Request $request): void
    {
        $request->validate([
            'br_code'          => 'required|integer|exists:branches,br_code',
            'inv_date'         => 'required|date',
            'party_code'       => 'required|integer|exists:parties,party_code',
            'items'            => 'required|array|min:1',
            'items.*.mat_code' => 'required|string|exists:products,mat_code',
            'items.*.qty'      => 'required|numeric|min:0.001',
            'items.*.uom'      => 'required|integer|exists:uoms,uom_code',
            'items.*.rate'     => 'required|numeric|min:0',
        ]);
    }
}
