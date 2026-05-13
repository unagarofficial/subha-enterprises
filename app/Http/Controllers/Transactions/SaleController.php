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

class SaleController extends Controller
{
    // ── List ─────────────────────────────────────────────────────────────────

    public function index(Request $request, $saleType)
    {
        $saleType  = (int) $saleType;
        $dateFrom  = $request->get('date_from', session('login_date'));
        $dateTo    = $request->get('date_to', session('login_date'));
        $partyCode = $request->get('party_code', '');
        $brCode    = $request->get('br_code', session('br_code'));
        $billType  = $request->get('bill_type', '');

        $query = DB::table('sale_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->join('branches as b', 'h.br_code', '=', 'b.br_code')
            ->where('h.fin_year_id', session('fin_year_id'))
            ->where('h.sale_type', $saleType)
            ->where('h.br_code', $brCode)
            ->whereBetween('h.inv_date', [$dateFrom, $dateTo]);

        if ($partyCode) $query->where('h.party_code', $partyCode);
        if ($billType)  $query->where('h.bill_type', $billType);

        $sales = $query
            ->orderBy('h.inv_date', 'desc')
            ->orderBy('h.inv_no', 'desc')
            ->select('h.*', 'p.party_name', 'p.place as party_place', 'b.br_name')
            ->get();

        $customers = Party::customers()->orderBy('party_name')->get();
        $branches  = Branch::orderBy('br_name')->get();
        $saleLabel = $saleType === 1 ? 'Cash Sale (Type 1)' : 'Credit Sale (Type 2)';

        return view('transactions.sale.index', compact(
            'sales', 'customers', 'branches', 'saleType', 'saleLabel',
            'dateFrom', 'dateTo', 'partyCode', 'brCode', 'billType'
        ));
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function create($saleType)
    {
        $saleType  = (int) $saleType;
        [$branches, $customers, $taxes, $uoms, $products] = $this->formData((int) session('br_code'));
        $nextInvNo = $this->nextInvNo((int) session('br_code'), (int) session('fin_year_id'), $saleType);
        $hdr       = null;
        $dtls      = collect();
        $firm      = Firm::first();
        $saleLabel = $saleType === 1 ? 'Cash Sale (Type 1)' : 'Credit Sale (Type 2)';

        return view('transactions.sale.form', compact(
            'hdr', 'dtls', 'branches', 'customers', 'taxes',
            'uoms', 'products', 'nextInvNo', 'saleType', 'saleLabel', 'firm'
        ));
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request, $saleType)
    {
        $saleType = (int) $saleType;
        $this->validateSale($request);

        DB::transaction(function () use ($request, $saleType) {
            $brCode = (int) $request->br_code;
            $invNo  = $this->nextInvNo($brCode, (int) session('fin_year_id'), $saleType);
            $firm   = Firm::first();
            $now    = now();

            DB::table('sale_hdr')->insert([
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
                'ord_no'      => ($request->ord_no ?? '') !== '' ? (int) $request->ord_no : null,
                'sale_type'   => $saleType,
                'is_locked'   => 0,
                'fin_year_id' => (int) session('fin_year_id'),
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $slNo = 1;
            foreach ($request->items as $row) {
                DB::table('sale_dtl')->insert([
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
                    'sale_type'  => $saleType,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->stockIssue($brCode, $row['mat_code'], (float) $row['qty']);
            }

            if (!empty($request->ord_no)) {
                DB::table('order_hdr')
                    ->where('br_code', $brCode)
                    ->where('ord_no', (int) $request->ord_no)
                    ->update(['inv_no' => $invNo, 'updated_at' => $now]);
            }
        });

        return redirect()->route('transactions.sale.index', ['saleType' => $saleType])
                         ->with('success', 'Sale invoice saved successfully.');
    }

    // ── Edit ─────────────────────────────────────────────────────────────────

    public function edit($saleType, $brCode, $invNo)
    {
        $saleType = (int) $saleType;
        $brCode   = (int) $brCode;
        $invNo    = (int) $invNo;

        $hdr = DB::table('sale_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->where('h.br_code', $brCode)
            ->where('h.inv_no', $invNo)
            ->where('h.sale_type', $saleType)
            ->select('h.*', 'p.party_name', 'p.place as party_place', 'p.state as party_state',
                     'p.inout_state', 'p.tin_grn_no', 'p.address as party_address')
            ->first();

        abort_if(!$hdr, 404);

        if ($hdr->is_locked) {
            return redirect()->route('transactions.sale.index', ['saleType' => $saleType])
                             ->with('error', 'Locked bills cannot be edited.');
        }

        $dtls = DB::table('sale_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->join('uoms as u', 'd.uom', '=', 'u.uom_code')
            ->where('d.br_code', $brCode)
            ->where('d.inv_no', $invNo)
            ->where('d.sale_type', $saleType)
            ->orderBy('d.sl_no')
            ->select('d.*', 'p.mat_name', 'u.uom_name')
            ->get();

        [$branches, $customers, $taxes, $uoms, $products] = $this->formData($brCode);
        $nextInvNo = $hdr->inv_no;
        $firm      = Firm::first();
        $saleLabel = $saleType === 1 ? 'Cash Sale (Type 1)' : 'Credit Sale (Type 2)';

        return view('transactions.sale.form', compact(
            'hdr', 'dtls', 'branches', 'customers', 'taxes',
            'uoms', 'products', 'nextInvNo', 'saleType', 'saleLabel', 'firm'
        ));
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, $saleType, $brCode, $invNo)
    {
        $saleType = (int) $saleType;
        $brCode   = (int) $brCode;
        $invNo    = (int) $invNo;

        $hdr = DB::table('sale_hdr')
            ->where('br_code', $brCode)->where('inv_no', $invNo)->where('sale_type', $saleType)
            ->first();
        abort_if(!$hdr, 404);

        if ($hdr->is_locked) {
            return back()->with('error', 'Locked bills cannot be edited. Unlock first.');
        }

        $this->validateSale($request);

        DB::transaction(function () use ($request, $saleType, $brCode, $invNo) {
            $now = now();

            $oldRows = DB::table('sale_dtl')
                ->where('br_code', $brCode)->where('inv_no', $invNo)->where('sale_type', $saleType)->get();
            foreach ($oldRows as $old) {
                $this->stockReverseIssue($brCode, $old->mat_code, (float) $old->qty);
            }

            DB::table('sale_dtl')
                ->where('br_code', $brCode)->where('inv_no', $invNo)->where('sale_type', $saleType)->delete();

            DB::table('sale_hdr')
                ->where('br_code', $brCode)->where('inv_no', $invNo)->where('sale_type', $saleType)
                ->update([
                    'inv_date'   => $request->inv_date,
                    'party_code' => (int) $request->party_code,
                    'gross'      => $request->gross,
                    'tax_rate'   => $request->tax_rate,
                    'tax_amount' => $request->tax_amount,
                    'nett'       => $request->nett,
                    'bill_type'  => $request->bill_type,
                    'ord_no'     => ($request->ord_no ?? '') !== '' ? (int) $request->ord_no : null,
                    'updated_at' => $now,
                ]);

            $slNo = 1;
            foreach ($request->items as $row) {
                DB::table('sale_dtl')->insert([
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
                    'sale_type'  => $saleType,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->stockIssue($brCode, $row['mat_code'], (float) $row['qty']);
            }
        });

        return redirect()->route('transactions.sale.index', ['saleType' => $saleType])
                         ->with('success', 'Sale invoice updated successfully.');
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function destroy($saleType, $brCode, $invNo)
    {
        $saleType = (int) $saleType;
        $brCode   = (int) $brCode;
        $invNo    = (int) $invNo;

        $hdr = DB::table('sale_hdr')
            ->where('br_code', $brCode)->where('inv_no', $invNo)->where('sale_type', $saleType)->first();
        abort_if(!$hdr, 404);

        if ($hdr->is_locked) {
            return back()->with('error', 'Locked bills cannot be deleted. Unlock first.');
        }

        DB::transaction(function () use ($saleType, $brCode, $invNo) {
            $rows = DB::table('sale_dtl')
                ->where('br_code', $brCode)->where('inv_no', $invNo)->where('sale_type', $saleType)->get();
            foreach ($rows as $row) {
                $this->stockReverseIssue($brCode, $row->mat_code, (float) $row->qty);
            }
            DB::table('sale_dtl')
                ->where('br_code', $brCode)->where('inv_no', $invNo)->where('sale_type', $saleType)->delete();
            DB::table('sale_hdr')
                ->where('br_code', $brCode)->where('inv_no', $invNo)->where('sale_type', $saleType)->delete();
        });

        return back()->with('success', 'Sale invoice deleted and stock reversed.');
    }

    // ── Toggle Lock (Admin only) ──────────────────────────────────────────────

    public function toggleLock($saleType, $brCode, $invNo)
    {
        if (session('user_type') !== 'ADMIN') {
            return back()->with('error', 'Only ADMIN can lock/unlock bills.');
        }

        $saleType = (int) $saleType;
        $brCode   = (int) $brCode;
        $invNo    = (int) $invNo;

        $hdr = DB::table('sale_hdr')
            ->where('br_code', $brCode)->where('inv_no', $invNo)->where('sale_type', $saleType)->first();
        abort_if(!$hdr, 404);

        $newLock = $hdr->is_locked ? 0 : 1;
        DB::table('sale_hdr')
            ->where('br_code', $brCode)->where('inv_no', $invNo)->where('sale_type', $saleType)
            ->update(['is_locked' => $newLock, 'updated_at' => now()]);

        return back()->with('success', $newLock ? 'Bill locked successfully.' : 'Bill unlocked successfully.');
    }

    // ── Print ─────────────────────────────────────────────────────────────────

    public function printBill(Request $request, $saleType, $brCode, $invNo)
    {
        $saleType = (int) $saleType;
        $brCode   = (int) $brCode;
        $invNo    = (int) $invNo;
        $format   = (int) ($request->get('format', 1));

        $hdr = DB::table('sale_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->join('branches as b', 'h.br_code', '=', 'b.br_code')
            ->where('h.br_code', $brCode)->where('h.inv_no', $invNo)->where('h.sale_type', $saleType)
            ->select('h.*', 'p.party_name', 'p.address as party_address', 'p.place as party_place',
                     'p.state as party_state', 'p.inout_state', 'p.tin_grn_no', 'b.br_name')
            ->first();

        abort_if(!$hdr, 404);

        $dtls = DB::table('sale_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->join('uoms as u', 'd.uom', '=', 'u.uom_code')
            ->where('d.br_code', $brCode)->where('d.inv_no', $invNo)->where('d.sale_type', $saleType)
            ->orderBy('d.sl_no')
            ->select('d.*', 'p.mat_name', 'u.uom_name')
            ->get();

        $firm = Firm::first();

        return view('transactions.sale.print', compact('hdr', 'dtls', 'firm', 'format', 'saleType'));
    }

    // ── AJAX: Party Details ───────────────────────────────────────────────────

    public function getParty($partyCode)
    {
        $party = Party::find((int) $partyCode);
        if (!$party) {
            return response()->json(['error' => 'Party not found'], 404);
        }

        return response()->json([
            'party_name'  => $party->party_name,
            'address'     => $party->address,
            'place'       => $party->place,
            'state'       => $party->state,
            'inout_state' => (int) $party->inout_state,
            'tin_grn_no'  => $party->tin_grn_no,
        ]);
    }

    // ── AJAX: Order Details ───────────────────────────────────────────────────

    public function getOrder(Request $request, $ordNo)
    {
        $brCode = (int) ($request->get('br_code', session('br_code')));

        $rows = DB::table('order_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->join('uoms as u', 'd.uom', '=', 'u.uom_code')
            ->where('d.br_code', $brCode)
            ->where('d.ord_no', (int) $ordNo)
            ->select('d.mat_code', 'p.mat_name', 'd.ord_qty as qty', 'd.uom',
                     'u.uom_name', 'p.sale_rate as rate', 'd.narration')
            ->get();

        return response()->json(['rows' => $rows]);
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

    private function nextInvNo(int $brCode, int $finYearId, int $saleType): int
    {
        return (int) (DB::table('sale_hdr')
            ->where('br_code', $brCode)
            ->where('fin_year_id', $finYearId)
            ->where('sale_type', $saleType)
            ->max('inv_no') ?? 0) + 1;
    }

    private function validateSale(Request $request): void
    {
        $request->validate([
            'br_code'          => 'required|integer|exists:branches,br_code',
            'inv_date'         => 'required|date',
            'party_code'       => 'required|integer|exists:parties,party_code',
            'bill_type'        => 'required|string|in:CASH,CREDIT,COVERING,PLATING',
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

    private function stockIssue(int $brCode, string $matCode, float $qty): void
    {
        $stock = Stock::where('br_code', $brCode)->where('mat_code', $matCode)->first();
        if ($stock) {
            $stock->issues   += $qty;
            $stock->cl_stock  = $stock->ob + $stock->rcpts - $stock->issues;
            $stock->save();
        } else {
            $product = Product::find($matCode);
            Stock::create([
                'br_code'  => $brCode, 'mat_code' => $matCode,
                'cat_code' => $product?->cat_code ?? 0,
                'ob' => 0, 'rcpts' => 0, 'issues' => $qty, 'cl_stock' => -$qty,
            ]);
        }
    }

    private function stockReverseIssue(int $brCode, string $matCode, float $qty): void
    {
        $stock = Stock::where('br_code', $brCode)->where('mat_code', $matCode)->first();
        if ($stock) {
            $stock->issues    = max(0, $stock->issues - $qty);
            $stock->cl_stock  = $stock->ob + $stock->rcpts - $stock->issues;
            $stock->save();
        }
    }
}
