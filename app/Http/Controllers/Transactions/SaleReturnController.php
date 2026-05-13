<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Firm;
use App\Models\Party;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Tax;
use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    // ── List ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $dateFrom  = $request->get('date_from', session('login_date'));
        $dateTo    = $request->get('date_to', session('login_date'));
        $partyCode = $request->get('party_code', '');
        $brCode    = $request->get('br_code', session('br_code'));
        $billType  = $request->get('bill_type', '');

        $query = DB::table('sale_rtn_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->join('branches as b', 'h.br_code', '=', 'b.br_code')
            ->where('h.fin_year_id', session('fin_year_id'))
            ->where('h.br_code', $brCode)
            ->whereBetween('h.inv_date', [$dateFrom, $dateTo]);

        if ($partyCode) $query->where('h.party_code', $partyCode);
        if ($billType)  $query->where('h.bill_type', $billType);

        $returns = $query
            ->orderBy('h.inv_date', 'desc')
            ->orderBy('h.inv_no', 'desc')
            ->select('h.*', 'p.party_name', 'p.place as party_place', 'b.br_name')
            ->get();

        $customers = Party::customers()->orderBy('party_name')->get();
        $branches  = Branch::orderBy('br_name')->get();

        return view('transactions.sale-return.index', compact(
            'returns', 'customers', 'branches',
            'dateFrom', 'dateTo', 'partyCode', 'brCode', 'billType'
        ));
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function create()
    {
        [$branches, $customers, $taxes, $uoms, $products] = $this->formData((int) session('br_code'));
        $nextInvNo = $this->nextInvNo((int) session('br_code'), (int) session('fin_year_id'));
        $hdr       = null;
        $dtls      = collect();

        return view('transactions.sale-return.form', compact(
            'hdr', 'dtls', 'branches', 'customers', 'taxes',
            'uoms', 'products', 'nextInvNo'
        ));
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->validateReturn($request);

        DB::transaction(function () use ($request) {
            $brCode = (int) $request->br_code;
            $invNo  = $this->nextInvNo($brCode, (int) session('fin_year_id'));
            $firm   = Firm::first();
            $now    = now();

            DB::table('sale_rtn_hdr')->insert([
                'ho_code'     => $firm?->ho_code,
                'br_code'     => $brCode,
                'inv_no'      => $invNo,
                'inv_date'    => $request->inv_date,
                'party_code'  => (int) $request->party_code,
                'gross'       => $request->gross,
                'tax_rate'    => $request->tax_rate,
                'tax_amount'  => $request->tax_amount,
                'nett'        => $request->nett,
                'bill_type'   => $request->bill_type,
                'fin_year_id' => (int) session('fin_year_id'),
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $slNo = 1;
            foreach ($request->items as $row) {
                DB::table('sale_rtn_dtl')->insert([
                    'br_code'    => $brCode,
                    'inv_no'     => $invNo,
                    'sl_no'      => $slNo++,
                    'mat_code'   => $row['mat_code'],
                    'qty'        => $row['qty'],
                    'uom'        => $row['uom'],
                    'rate'       => $row['rate'],
                    's_value'    => $row['s_value'],
                    'narration'  => $row['narration'] ?? null,
                    'inv_date'   => $request->inv_date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                // Sale return: goods come back → issues decrease
                $this->stockReturnIn($brCode, $row['mat_code'], (float) $row['qty']);
            }
        });

        return redirect()->route('transactions.sale-return.index')
                         ->with('success', 'Sale Return saved successfully.');
    }

    // ── Edit ─────────────────────────────────────────────────────────────────

    public function edit($brCode, $invNo)
    {
        $hdr = DB::table('sale_rtn_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->where('h.br_code', $brCode)->where('h.inv_no', $invNo)
            ->select('h.*', 'p.party_name', 'p.place as party_place')
            ->first();

        abort_if(!$hdr, 404);

        $dtls = DB::table('sale_rtn_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->join('uoms as u', 'd.uom', '=', 'u.uom_code')
            ->where('d.br_code', $brCode)->where('d.inv_no', $invNo)
            ->orderBy('d.sl_no')
            ->select('d.*', 'p.mat_name', 'u.uom_name')
            ->get();

        [$branches, $customers, $taxes, $uoms, $products] = $this->formData((int) $brCode);
        $nextInvNo = $hdr->inv_no;

        return view('transactions.sale-return.form', compact(
            'hdr', 'dtls', 'branches', 'customers', 'taxes',
            'uoms', 'products', 'nextInvNo'
        ));
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, $brCode, $invNo)
    {
        $this->validateReturn($request);

        DB::transaction(function () use ($request, $brCode, $invNo) {
            $now = now();

            // Reverse old stock changes
            $oldRows = DB::table('sale_rtn_dtl')
                ->where('br_code', $brCode)->where('inv_no', $invNo)->get();
            foreach ($oldRows as $old) {
                $this->stockReturnOut($brCode, $old->mat_code, (float) $old->qty);
            }

            DB::table('sale_rtn_dtl')->where('br_code', $brCode)->where('inv_no', $invNo)->delete();

            DB::table('sale_rtn_hdr')
                ->where('br_code', $brCode)->where('inv_no', $invNo)
                ->update([
                    'inv_date'   => $request->inv_date,
                    'party_code' => (int) $request->party_code,
                    'gross'      => $request->gross,
                    'tax_rate'   => $request->tax_rate,
                    'tax_amount' => $request->tax_amount,
                    'nett'       => $request->nett,
                    'bill_type'  => $request->bill_type,
                    'updated_at' => $now,
                ]);

            $slNo = 1;
            foreach ($request->items as $row) {
                DB::table('sale_rtn_dtl')->insert([
                    'br_code'    => $brCode,
                    'inv_no'     => $invNo,
                    'sl_no'      => $slNo++,
                    'mat_code'   => $row['mat_code'],
                    'qty'        => $row['qty'],
                    'uom'        => $row['uom'],
                    'rate'       => $row['rate'],
                    's_value'    => $row['s_value'],
                    'narration'  => $row['narration'] ?? null,
                    'inv_date'   => $request->inv_date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->stockReturnIn($brCode, $row['mat_code'], (float) $row['qty']);
            }
        });

        return redirect()->route('transactions.sale-return.index')
                         ->with('success', 'Sale Return updated successfully.');
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function destroy($brCode, $invNo)
    {
        DB::transaction(function () use ($brCode, $invNo) {
            $rows = DB::table('sale_rtn_dtl')
                ->where('br_code', $brCode)->where('inv_no', $invNo)->get();
            foreach ($rows as $row) {
                $this->stockReturnOut($brCode, $row->mat_code, (float) $row->qty);
            }
            DB::table('sale_rtn_dtl')->where('br_code', $brCode)->where('inv_no', $invNo)->delete();
            DB::table('sale_rtn_hdr')->where('br_code', $brCode)->where('inv_no', $invNo)->delete();
        });

        return back()->with('success', 'Sale Return deleted and stock reversed.');
    }

    // ── AJAX: Customer sales list ─────────────────────────────────────────────

    public function getCustomerSales(Request $request, $partyCode)
    {
        $brCode = (int) ($request->get('br_code', session('br_code')));

        $sales = DB::table('sale_hdr')
            ->where('br_code', $brCode)
            ->where('party_code', (int) $partyCode)
            ->where('fin_year_id', session('fin_year_id'))
            ->orderBy('inv_no', 'desc')
            ->select('inv_no', 'inv_date', 'gross', 'nett', 'bill_type', 'sale_type')
            ->get();

        return response()->json($sales);
    }

    // ── AJAX: Sale invoice items ──────────────────────────────────────────────

    public function getSaleItems(Request $request, $brCode, $invNo, $saleType)
    {
        $rows = DB::table('sale_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->join('uoms as u', 'd.uom', '=', 'u.uom_code')
            ->where('d.br_code', (int) $brCode)
            ->where('d.inv_no', (int) $invNo)
            ->where('d.sale_type', (int) $saleType)
            ->orderBy('d.sl_no')
            ->select('d.mat_code', 'p.mat_name', 'd.qty', 'd.uom', 'u.uom_name', 'd.rate', 'd.s_value', 'd.narration')
            ->get();

        return response()->json(['rows' => $rows]);
    }

    // ── Print ─────────────────────────────────────────────────────────────────

    public function printReturn($brCode, $invNo)
    {
        $hdr = DB::table('sale_rtn_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->join('branches as b', 'h.br_code', '=', 'b.br_code')
            ->where('h.br_code', $brCode)->where('h.inv_no', $invNo)
            ->select('h.*', 'p.party_name', 'p.address as party_address', 'p.place as party_place',
                     'p.tin_grn_no', 'b.br_name')
            ->first();

        abort_if(!$hdr, 404);

        $dtls = DB::table('sale_rtn_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->join('uoms as u', 'd.uom', '=', 'u.uom_code')
            ->where('d.br_code', $brCode)->where('d.inv_no', $invNo)
            ->orderBy('d.sl_no')
            ->select('d.*', 'p.mat_name', 'u.uom_name')
            ->get();

        $firm = Firm::first();

        return view('transactions.sale-return.print', compact('hdr', 'dtls', 'firm'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function formData(int $brCode): array
    {
        return [
            Branch::orderBy('br_name')->get(),
            Party::customers()->orderBy('party_name')->get(),
            Tax::orderBy('tax_name')->get(),
            Uom::orderBy('uom_name')->get(),
            Product::with(['uomUnit'])->where('br_code', $brCode)->orderBy('mat_name')->get(),
        ];
    }

    private function nextInvNo(int $brCode, int $finYearId): int
    {
        return (int) (DB::table('sale_rtn_hdr')
            ->where('br_code', $brCode)
            ->where('fin_year_id', $finYearId)
            ->max('inv_no') ?? 0) + 1;
    }

    private function validateReturn(Request $request): void
    {
        $request->validate([
            'br_code'          => 'required|integer|exists:branches,br_code',
            'inv_date'         => 'required|date',
            'party_code'       => 'required|integer|exists:parties,party_code',
            'bill_type'        => 'required|string|in:COVERING,PLATING',
            'gross'            => 'required|numeric|min:0',
            'tax_rate'         => 'required|numeric|min:0',
            'tax_amount'       => 'required|numeric|min:0',
            'nett'             => 'required|numeric|min:0',
            'items'            => 'required|array|min:1',
            'items.*.mat_code' => 'required|string|exists:products,mat_code',
            'items.*.qty'      => 'required|numeric|min:0.001',
            'items.*.uom'      => 'required|integer|exists:uoms,uom_code',
            'items.*.rate'     => 'required|numeric|min:0',
            'items.*.s_value'  => 'required|numeric|min:0',
        ]);
    }

    // Sale return → goods come back → issues decrease
    private function stockReturnIn(int $brCode, string $matCode, float $qty): void
    {
        $stock = Stock::where('br_code', $brCode)->where('mat_code', $matCode)->first();
        if ($stock) {
            $stock->issues   = max(0, $stock->issues - $qty);
            $stock->cl_stock = $stock->ob + $stock->rcpts - $stock->issues;
            $stock->save();
        }
    }

    // Reverse a sale return (delete/update) → issues increase back
    private function stockReturnOut(int $brCode, string $matCode, float $qty): void
    {
        $stock = Stock::where('br_code', $brCode)->where('mat_code', $matCode)->first();
        if ($stock) {
            $stock->issues   += $qty;
            $stock->cl_stock  = $stock->ob + $stock->rcpts - $stock->issues;
            $stock->save();
        }
    }
}
