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

class PurchaseReturnController extends Controller
{
    // ── List ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $dateFrom  = $request->get('date_from', session('login_date'));
        $dateTo    = $request->get('date_to', session('login_date'));
        $partyCode = $request->get('party_code', '');
        $brCode    = $request->get('br_code', session('br_code'));
        $rtnType   = $request->get('rtn_type', '');

        $query = DB::table('purchase_rtn_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->join('branches as b', 'h.br_code', '=', 'b.br_code')
            ->where('h.fin_year_id', session('fin_year_id'))
            ->where('h.br_code', $brCode)
            ->whereBetween('h.inv_date', [$dateFrom, $dateTo]);

        if ($partyCode) $query->where('h.party_code', $partyCode);
        if ($rtnType)   $query->where('h.rtn_type', $rtnType);

        $returns = $query
            ->orderBy('h.inv_date', 'desc')
            ->orderBy('h.inv_no', 'desc')
            ->select('h.*', 'p.party_name', 'p.place as party_place', 'b.br_name')
            ->get();

        $suppliers = Party::suppliers()->orderBy('party_name')->get();
        $branches  = Branch::orderBy('br_name')->get();

        return view('transactions.purchase-return.index', compact(
            'returns', 'suppliers', 'branches',
            'dateFrom', 'dateTo', 'partyCode', 'brCode', 'rtnType'
        ));
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function create()
    {
        [$branches, $suppliers, $taxes, $uoms, $products] = $this->formData((int) session('br_code'));
        $nextInvNo = $this->nextInvNo((int) session('br_code'), (int) session('fin_year_id'));
        $hdr       = null;
        $dtls      = collect();

        return view('transactions.purchase-return.form', compact(
            'hdr', 'dtls', 'branches', 'suppliers', 'taxes',
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
            $now    = now();

            DB::table('purchase_rtn_hdr')->insert([
                'br_code'     => $brCode,
                'inv_no'      => $invNo,
                'inv_date'    => $request->inv_date,
                'party_code'  => (int) $request->party_code,
                'gross'       => $request->gross,
                'tax_rate'    => $request->tax_rate,
                'tax_amount'  => $request->tax_amount,
                'nett'        => $request->nett,
                'rtn_type'    => $request->rtn_type,
                'fin_year_id' => (int) session('fin_year_id'),
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $slNo = 1;
            foreach ($request->items as $row) {
                DB::table('purchase_rtn_dtl')->insert([
                    'br_code'    => $brCode,
                    'inv_no'     => $invNo,
                    'sl_no'      => $slNo++,
                    'mat_code'   => $row['mat_code'],
                    'qty'        => $row['qty'],
                    'uom'        => $row['uom'],
                    'rate'       => $row['rate'],
                    'amount'     => $row['amount'],
                    'narration'  => $row['narration'] ?? null,
                    'inv_date'   => $request->inv_date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                // Purchase return: goods sent back → rcpts decrease
                $this->stockReturnOut($brCode, $row['mat_code'], (float) $row['qty']);
            }
        });

        return redirect()->route('transactions.purchase-return.index')
                         ->with('success', 'Purchase Return saved successfully.');
    }

    // ── Edit ─────────────────────────────────────────────────────────────────

    public function edit($brCode, $invNo)
    {
        $hdr = DB::table('purchase_rtn_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->where('h.br_code', $brCode)->where('h.inv_no', $invNo)
            ->select('h.*', 'p.party_name', 'p.place as party_place')
            ->first();

        abort_if(!$hdr, 404);

        $dtls = DB::table('purchase_rtn_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->join('uoms as u', 'd.uom', '=', 'u.uom_code')
            ->where('d.br_code', $brCode)->where('d.inv_no', $invNo)
            ->orderBy('d.sl_no')
            ->select('d.*', 'p.mat_name', 'u.uom_name')
            ->get();

        [$branches, $suppliers, $taxes, $uoms, $products] = $this->formData((int) $brCode);
        $nextInvNo = $hdr->inv_no;

        return view('transactions.purchase-return.form', compact(
            'hdr', 'dtls', 'branches', 'suppliers', 'taxes',
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
            $oldRows = DB::table('purchase_rtn_dtl')
                ->where('br_code', $brCode)->where('inv_no', $invNo)->get();
            foreach ($oldRows as $old) {
                $this->stockReturnIn($brCode, $old->mat_code, (float) $old->qty);
            }

            DB::table('purchase_rtn_dtl')->where('br_code', $brCode)->where('inv_no', $invNo)->delete();

            DB::table('purchase_rtn_hdr')
                ->where('br_code', $brCode)->where('inv_no', $invNo)
                ->update([
                    'inv_date'   => $request->inv_date,
                    'party_code' => (int) $request->party_code,
                    'gross'      => $request->gross,
                    'tax_rate'   => $request->tax_rate,
                    'tax_amount' => $request->tax_amount,
                    'nett'       => $request->nett,
                    'rtn_type'   => $request->rtn_type,
                    'updated_at' => $now,
                ]);

            $slNo = 1;
            foreach ($request->items as $row) {
                DB::table('purchase_rtn_dtl')->insert([
                    'br_code'    => $brCode,
                    'inv_no'     => $invNo,
                    'sl_no'      => $slNo++,
                    'mat_code'   => $row['mat_code'],
                    'qty'        => $row['qty'],
                    'uom'        => $row['uom'],
                    'rate'       => $row['rate'],
                    'amount'     => $row['amount'],
                    'narration'  => $row['narration'] ?? null,
                    'inv_date'   => $request->inv_date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->stockReturnOut($brCode, $row['mat_code'], (float) $row['qty']);
            }
        });

        return redirect()->route('transactions.purchase-return.index')
                         ->with('success', 'Purchase Return updated successfully.');
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function destroy($brCode, $invNo)
    {
        DB::transaction(function () use ($brCode, $invNo) {
            $rows = DB::table('purchase_rtn_dtl')
                ->where('br_code', $brCode)->where('inv_no', $invNo)->get();
            foreach ($rows as $row) {
                $this->stockReturnIn($brCode, $row->mat_code, (float) $row->qty);
            }
            DB::table('purchase_rtn_dtl')->where('br_code', $brCode)->where('inv_no', $invNo)->delete();
            DB::table('purchase_rtn_hdr')->where('br_code', $brCode)->where('inv_no', $invNo)->delete();
        });

        return back()->with('success', 'Purchase Return deleted and stock reversed.');
    }

    // ── AJAX: Supplier purchases list ─────────────────────────────────────────

    public function getSupplierPurchases(Request $request, $partyCode)
    {
        $brCode = (int) ($request->get('br_code', session('br_code')));

        $purchases = DB::table('purchase_hdr')
            ->where('br_code', $brCode)
            ->where('party_code', (int) $partyCode)
            ->where('fin_year_id', session('fin_year_id'))
            ->orderBy('inv_no', 'desc')
            ->select('inv_no', 'inv_date', 'gross', 'nett')
            ->get();

        return response()->json($purchases);
    }

    // ── AJAX: Purchase invoice items ──────────────────────────────────────────

    public function getPurchaseItems(Request $request, $brCode, $invNo)
    {
        $rows = DB::table('purchase_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->join('uoms as u', 'd.uom', '=', 'u.uom_code')
            ->where('d.br_code', (int) $brCode)
            ->where('d.inv_no', (int) $invNo)
            ->orderBy('d.sl_no')
            ->select('d.mat_code', 'p.mat_name', 'd.qty', 'd.uom', 'u.uom_name', 'd.rate', 'd.amount', 'd.narration')
            ->get();

        return response()->json(['rows' => $rows]);
    }

    // ── Print ─────────────────────────────────────────────────────────────────

    public function printReturn($brCode, $invNo)
    {
        $hdr = DB::table('purchase_rtn_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->join('branches as b', 'h.br_code', '=', 'b.br_code')
            ->where('h.br_code', $brCode)->where('h.inv_no', $invNo)
            ->select('h.*', 'p.party_name', 'p.address as party_address', 'p.place as party_place',
                     'p.tin_grn_no', 'b.br_name')
            ->first();

        abort_if(!$hdr, 404);

        $dtls = DB::table('purchase_rtn_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->join('uoms as u', 'd.uom', '=', 'u.uom_code')
            ->where('d.br_code', $brCode)->where('d.inv_no', $invNo)
            ->orderBy('d.sl_no')
            ->select('d.*', 'p.mat_name', 'u.uom_name')
            ->get();

        $firm = Firm::first();

        return view('transactions.purchase-return.print', compact('hdr', 'dtls', 'firm'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function formData(int $brCode): array
    {
        return [
            Branch::orderBy('br_name')->get(),
            Party::suppliers()->orderBy('party_name')->get(),
            Tax::orderBy('tax_name')->get(),
            Uom::orderBy('uom_name')->get(),
            Product::with(['uomUnit'])->where('br_code', $brCode)->orderBy('mat_name')->get(),
        ];
    }

    private function nextInvNo(int $brCode, int $finYearId): int
    {
        return (int) (DB::table('purchase_rtn_hdr')
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
            'rtn_type'         => 'required|string|in:COVERING,PLATING',
            'gross'            => 'required|numeric|min:0',
            'tax_rate'         => 'required|numeric|min:0',
            'tax_amount'       => 'required|numeric|min:0',
            'nett'             => 'required|numeric|min:0',
            'items'            => 'required|array|min:1',
            'items.*.mat_code' => 'required|string|exists:products,mat_code',
            'items.*.qty'      => 'required|numeric|min:0.001',
            'items.*.uom'      => 'required|integer|exists:uoms,uom_code',
            'items.*.rate'     => 'required|numeric|min:0',
            'items.*.amount'   => 'required|numeric|min:0',
        ]);
    }

    // Purchase return → goods go out → rcpts decrease
    private function stockReturnOut(int $brCode, string $matCode, float $qty): void
    {
        $stock = Stock::where('br_code', $brCode)->where('mat_code', $matCode)->first();
        if ($stock) {
            $stock->rcpts    = max(0, $stock->rcpts - $qty);
            $stock->cl_stock = $stock->ob + $stock->rcpts - $stock->issues;
            $stock->save();
        }
    }

    // Reverse a purchase return (delete/update) → rcpts increase back
    private function stockReturnIn(int $brCode, string $matCode, float $qty): void
    {
        $stock = Stock::where('br_code', $brCode)->where('mat_code', $matCode)->first();
        if ($stock) {
            $stock->rcpts   += $qty;
            $stock->cl_stock = $stock->ob + $stock->rcpts - $stock->issues;
            $stock->save();
        }
    }
}
