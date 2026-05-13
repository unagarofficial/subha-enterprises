<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Firm;
use App\Models\Party;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // ── List ─────────────────────────────────────────────────────────────────

    public function index(Request $request, $ordType)
    {
        $ordType   = (int) $ordType;
        $dateFrom  = $request->get('date_from', session('login_date'));
        $dateTo    = $request->get('date_to', session('login_date'));
        $partyCode = $request->get('party_code', '');
        $brCode    = $request->get('br_code', session('br_code'));
        $status    = $request->get('status', '');

        $query = DB::table('order_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->where('h.fin_year_id', session('fin_year_id'))
            ->where('h.ord_type', $ordType)
            ->where('h.br_code', $brCode)
            ->whereBetween('h.ord_date', [$dateFrom, $dateTo]);

        if ($partyCode) $query->where('h.party_code', $partyCode);
        if ($status === 'open')      $query->whereNull('h.inv_no')->where('h.is_locked', 0);
        if ($status === 'locked')    $query->where('h.is_locked', 1)->whereNull('h.inv_no');
        if ($status === 'converted') $query->whereNotNull('h.inv_no');

        $orders = $query
            ->orderBy('h.ord_date', 'desc')
            ->orderBy('h.ord_no', 'desc')
            ->select('h.*', 'p.party_name', 'p.place as party_place')
            ->get();

        $customers = Party::customers()->orderBy('party_name')->get();
        $branches  = Branch::orderBy('br_name')->get();
        $ordLabel  = $ordType === 1 ? 'Order Type 1' : 'Order Type 2';

        return view('transactions.order.index', compact(
            'orders', 'customers', 'branches', 'ordType', 'ordLabel',
            'dateFrom', 'dateTo', 'partyCode', 'brCode', 'status'
        ));
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function create($ordType)
    {
        $ordType   = (int) $ordType;
        $brCode    = (int) session('br_code');
        [$branches, $customers, $uoms, $products] = $this->formData($brCode);
        $nextOrdNo = $this->nextOrdNo($brCode, (int) session('fin_year_id'), $ordType);
        $hdr       = null;
        $dtls      = collect();
        $firm      = Firm::first();
        $ordLabel  = $ordType === 1 ? 'Order Type 1' : 'Order Type 2';

        return view('transactions.order.form', compact(
            'hdr', 'dtls', 'branches', 'customers', 'uoms', 'products',
            'nextOrdNo', 'ordType', 'ordLabel', 'firm'
        ));
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request, $ordType)
    {
        $ordType = (int) $ordType;
        $this->validateOrder($request);

        DB::transaction(function () use ($request, $ordType) {
            $brCode = (int) $request->br_code;
            $ordNo  = $this->nextOrdNo($brCode, (int) session('fin_year_id'), $ordType);
            $firm   = Firm::first();
            $now    = now();

            DB::table('order_hdr')->insert([
                'ho_code'     => $firm?->ho_code,
                'br_code'     => $brCode,
                'ord_no'      => $ordNo,
                'ord_date'    => $request->ord_date,
                'party_code'  => (int) $request->party_code,
                'is_locked'   => $request->has('is_locked') ? 1 : 0,
                'inv_no'      => null,
                'ord_type'    => $ordType,
                'fin_year_id' => (int) session('fin_year_id'),
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $slNo = 1;
            foreach ($request->items as $row) {
                $ordQty  = (float) ($row['ord_qty'] ?? 0);
                $billQty = (float) ($row['bill_qty'] ?? 0);
                $exQty   = (float) ($row['ex_qty'] ?? 0);
                $pQty    = (float) ($row['p_qty'] ?? 0);
                $reqQty  = max(0, $ordQty - $billQty);

                DB::table('order_dtl')->insert([
                    'br_code'    => $brCode,
                    'ord_no'     => $ordNo,
                    'sl_no'      => $slNo++,
                    'mat_code'   => $row['mat_code'],
                    'narration'  => $row['narration'] ?? null,
                    'ord_qty'    => $ordQty,
                    'uom'        => (int) $row['uom'],
                    'bill_qty'   => $billQty,
                    'ex_qty'     => $exQty,
                    'po_no'      => !empty($row['po_no']) ? (int) $row['po_no'] : null,
                    'po_date'    => !empty($row['po_date']) ? $row['po_date'] : null,
                    'p_qty'      => $pQty,
                    'pb_no'      => !empty($row['pb_no']) ? (int) $row['pb_no'] : null,
                    'req_qty'    => $reqQty,
                    'ord_type'   => $ordType,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        return redirect()->route('transactions.order.index', ['ordType' => $ordType])
                         ->with('success', 'Order saved successfully.');
    }

    // ── Edit ─────────────────────────────────────────────────────────────────

    public function edit($ordType, $brCode, $ordNo)
    {
        $ordType = (int) $ordType;
        $brCode  = (int) $brCode;
        $ordNo   = (int) $ordNo;

        $hdr = DB::table('order_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->where('h.br_code', $brCode)
            ->where('h.ord_no', $ordNo)
            ->where('h.ord_type', $ordType)
            ->select('h.*', 'p.party_name', 'p.place as party_place', 'p.address as party_address')
            ->first();

        abort_if(!$hdr, 404);

        if ($hdr->inv_no) {
            return redirect()->route('transactions.order.index', ['ordType' => $ordType])
                             ->with('error', 'Converted orders cannot be edited.');
        }

        $dtls = DB::table('order_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->join('uoms as u', 'd.uom', '=', 'u.uom_code')
            ->where('d.br_code', $brCode)
            ->where('d.ord_no', $ordNo)
            ->orderBy('d.sl_no')
            ->select('d.*', 'p.mat_name', 'u.uom_name')
            ->get();

        [$branches, $customers, $uoms, $products] = $this->formData($brCode);
        $nextOrdNo = $hdr->ord_no;
        $firm      = Firm::first();
        $ordLabel  = $ordType === 1 ? 'Order Type 1' : 'Order Type 2';

        return view('transactions.order.form', compact(
            'hdr', 'dtls', 'branches', 'customers', 'uoms', 'products',
            'nextOrdNo', 'ordType', 'ordLabel', 'firm'
        ));
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, $ordType, $brCode, $ordNo)
    {
        $ordType = (int) $ordType;
        $brCode  = (int) $brCode;
        $ordNo   = (int) $ordNo;

        $hdr = DB::table('order_hdr')
            ->where('br_code', $brCode)->where('ord_no', $ordNo)->where('ord_type', $ordType)->first();
        abort_if(!$hdr, 404);

        if ($hdr->inv_no) {
            return back()->with('error', 'Converted orders cannot be edited.');
        }

        $this->validateOrder($request);

        DB::transaction(function () use ($request, $ordType, $brCode, $ordNo) {
            $now = now();

            DB::table('order_hdr')
                ->where('br_code', $brCode)->where('ord_no', $ordNo)->where('ord_type', $ordType)
                ->update([
                    'ord_date'   => $request->ord_date,
                    'party_code' => (int) $request->party_code,
                    'is_locked'  => $request->has('is_locked') ? 1 : 0,
                    'updated_at' => $now,
                ]);

            DB::table('order_dtl')
                ->where('br_code', $brCode)->where('ord_no', $ordNo)->delete();

            $slNo = 1;
            foreach ($request->items as $row) {
                $ordQty  = (float) ($row['ord_qty'] ?? 0);
                $billQty = (float) ($row['bill_qty'] ?? 0);
                $exQty   = (float) ($row['ex_qty'] ?? 0);
                $pQty    = (float) ($row['p_qty'] ?? 0);
                $reqQty  = max(0, $ordQty - $billQty);

                DB::table('order_dtl')->insert([
                    'br_code'    => $brCode,
                    'ord_no'     => $ordNo,
                    'sl_no'      => $slNo++,
                    'mat_code'   => $row['mat_code'],
                    'narration'  => $row['narration'] ?? null,
                    'ord_qty'    => $ordQty,
                    'uom'        => (int) $row['uom'],
                    'bill_qty'   => $billQty,
                    'ex_qty'     => $exQty,
                    'po_no'      => !empty($row['po_no']) ? (int) $row['po_no'] : null,
                    'po_date'    => !empty($row['po_date']) ? $row['po_date'] : null,
                    'p_qty'      => $pQty,
                    'pb_no'      => !empty($row['pb_no']) ? (int) $row['pb_no'] : null,
                    'req_qty'    => $reqQty,
                    'ord_type'   => $ordType,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        return redirect()->route('transactions.order.index', ['ordType' => $ordType])
                         ->with('success', 'Order updated successfully.');
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function destroy($ordType, $brCode, $ordNo)
    {
        $ordType = (int) $ordType;
        $brCode  = (int) $brCode;
        $ordNo   = (int) $ordNo;

        $hdr = DB::table('order_hdr')
            ->where('br_code', $brCode)->where('ord_no', $ordNo)->where('ord_type', $ordType)->first();
        abort_if(!$hdr, 404);

        if ($hdr->inv_no) {
            return back()->with('error', 'Converted orders cannot be deleted.');
        }
        if ($hdr->is_locked) {
            return back()->with('error', 'Locked orders cannot be deleted. Unlock first.');
        }

        DB::transaction(function () use ($ordType, $brCode, $ordNo) {
            DB::table('order_dtl')->where('br_code', $brCode)->where('ord_no', $ordNo)->delete();
            DB::table('order_hdr')
                ->where('br_code', $brCode)->where('ord_no', $ordNo)->where('ord_type', $ordType)->delete();
        });

        return back()->with('success', 'Order deleted.');
    }

    // ── Lock / Unlock ─────────────────────────────────────────────────────────

    public function lockOrder($ordType, $brCode, $ordNo)
    {
        $ordType = (int) $ordType;
        $brCode  = (int) $brCode;
        $ordNo   = (int) $ordNo;

        $hdr = DB::table('order_hdr')
            ->where('br_code', $brCode)->where('ord_no', $ordNo)->where('ord_type', $ordType)->first();
        abort_if(!$hdr, 404);

        if ($hdr->inv_no) {
            return back()->with('error', 'Converted orders cannot be locked/unlocked.');
        }

        $newLock = $hdr->is_locked ? 0 : 1;
        DB::table('order_hdr')
            ->where('br_code', $brCode)->where('ord_no', $ordNo)->where('ord_type', $ordType)
            ->update(['is_locked' => $newLock, 'updated_at' => now()]);

        return back()->with('success', $newLock ? 'Order locked.' : 'Order unlocked.');
    }

    // ── Convert to Sale Bill ──────────────────────────────────────────────────

    public function convertToSale(Request $request, $ordType, $brCode, $ordNo)
    {
        $request->validate([
            'sale_type' => 'required|in:1,2',
            'bill_type' => 'required|in:CASH,CREDIT,COVERING,PLATING',
        ]);

        $ordType  = (int) $ordType;
        $brCode   = (int) $brCode;
        $ordNo    = (int) $ordNo;
        $saleType = (int) $request->sale_type;
        $billType = $request->bill_type;

        $hdr = DB::table('order_hdr')
            ->where('br_code', $brCode)->where('ord_no', $ordNo)->where('ord_type', $ordType)->first();
        abort_if(!$hdr, 404);

        if ($hdr->inv_no) {
            return back()->with('error', 'This order is already converted to Sale Invoice #' . $hdr->inv_no . '.');
        }

        $dtls = DB::table('order_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->where('d.br_code', $brCode)
            ->where('d.ord_no', $ordNo)
            ->select('d.*', 'p.sale_rate')
            ->get();

        if ($dtls->isEmpty()) {
            return back()->with('error', 'No items found in this order.');
        }

        $firm  = Firm::first();
        $invNo = null;

        DB::transaction(function () use ($hdr, $dtls, $brCode, $ordNo, $saleType, $billType, $firm, $ordType, &$invNo) {
            $now       = now();
            $finYearId = (int) session('fin_year_id');

            $invNo = (int) (DB::table('sale_hdr')
                ->where('br_code', $brCode)
                ->where('fin_year_id', $finYearId)
                ->where('sale_type', $saleType)
                ->max('inv_no') ?? 0) + 1;

            $gross = 0;
            foreach ($dtls as $d) {
                $qty    = (float) $d->bill_qty > 0 ? (float) $d->bill_qty : (float) $d->ord_qty;
                $gross += $qty * (float) $d->sale_rate;
            }

            DB::table('sale_hdr')->insert([
                'ho_code'     => $firm?->ho_code,
                'br_code'     => $brCode,
                'inv_no'      => $invNo,
                'inv_date'    => $hdr->ord_date,
                'party_code'  => $hdr->party_code,
                'gross'       => $gross,
                'tax_rate'    => 0,
                'tax_amount'  => 0,
                'nett'        => $gross,
                'bill_type'   => $billType,
                'ord_no'      => $ordNo,
                'sale_type'   => $saleType,
                'is_locked'   => 0,
                'fin_year_id' => $finYearId,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $slNo = 1;
            foreach ($dtls as $d) {
                $qty    = (float) $d->bill_qty > 0 ? (float) $d->bill_qty : (float) $d->ord_qty;
                $rate   = (float) $d->sale_rate;
                $sValue = round($qty * $rate, 2);

                DB::table('sale_dtl')->insert([
                    'br_code'    => $brCode,
                    'inv_no'     => $invNo,
                    'sl_no'      => $slNo++,
                    'mat_code'   => $d->mat_code,
                    'qty'        => $qty,
                    'uom'        => $d->uom,
                    'rate'       => $rate,
                    's_value'    => $sValue,
                    'narration'  => $d->narration,
                    'inv_date'   => $hdr->ord_date,
                    'sale_type'  => $saleType,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $stock = Stock::where('br_code', $brCode)->where('mat_code', $d->mat_code)->first();
                if ($stock) {
                    $stock->issues  += $qty;
                    $stock->cl_stock = $stock->ob + $stock->rcpts - $stock->issues;
                    $stock->save();
                }
            }

            DB::table('order_hdr')
                ->where('br_code', $brCode)->where('ord_no', $ordNo)->where('ord_type', $ordType)
                ->update(['inv_no' => $invNo, 'is_locked' => 1, 'updated_at' => $now]);
        });

        return redirect()->route('transactions.sale.edit', [$saleType, $brCode, $invNo])
                         ->with('success', 'Order #' . $ordNo . ' converted to Sale Invoice #' . $invNo . '. Please review and save.');
    }

    // ── Print Estimation Bill ─────────────────────────────────────────────────

    public function printEstimation($ordType, $brCode, $ordNo)
    {
        $ordType = (int) $ordType;
        $brCode  = (int) $brCode;
        $ordNo   = (int) $ordNo;

        $hdr = DB::table('order_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->where('h.br_code', $brCode)
            ->where('h.ord_no', $ordNo)
            ->where('h.ord_type', $ordType)
            ->select('h.*', 'p.party_name', 'p.address as party_address',
                     'p.place as party_place', 'p.state as party_state')
            ->first();

        abort_if(!$hdr, 404);

        $dtls = DB::table('order_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->join('uoms as u', 'd.uom', '=', 'u.uom_code')
            ->where('d.br_code', $brCode)
            ->where('d.ord_no', $ordNo)
            ->orderBy('d.sl_no')
            ->select('d.*', 'p.mat_name', 'p.sale_rate as rate', 'u.uom_name')
            ->get();

        $firm = Firm::first();

        return view('transactions.order.print', compact('hdr', 'dtls', 'firm', 'ordType'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function formData(int $brCode): array
    {
        return [
            Branch::orderBy('br_name')->get(),
            Party::customers()->orderBy('party_name')->get(),
            Uom::orderBy('uom_name')->get(),
            Product::with(['uomUnit'])->where('br_code', $brCode)->orderBy('mat_name')->get(),
        ];
    }

    private function nextOrdNo(int $brCode, int $finYearId, int $ordType): int
    {
        return (int) (DB::table('order_hdr')
            ->where('br_code', $brCode)
            ->where('fin_year_id', $finYearId)
            ->where('ord_type', $ordType)
            ->max('ord_no') ?? 0) + 1;
    }

    private function validateOrder(Request $request): void
    {
        $request->validate([
            'br_code'          => 'required|integer|exists:branches,br_code',
            'ord_date'         => 'required|date',
            'party_code'       => 'required|integer|exists:parties,party_code',
            'items'            => 'required|array|min:1',
            'items.*.mat_code' => 'required|string|exists:products,mat_code',
            'items.*.ord_qty'  => 'required|numeric|min:0.001',
            'items.*.uom'      => 'required|integer|exists:uoms,uom_code',
        ]);
    }
}
