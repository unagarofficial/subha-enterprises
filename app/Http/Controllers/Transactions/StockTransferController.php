<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Firm;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    // ── List ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $dateFrom = $request->get('date_from', session('login_date'));
        $dateTo   = $request->get('date_to', session('login_date'));
        $brCode   = $request->get('br_code', session('br_code'));

        $transfers = DB::table('branch_issue_hdr as h')
            ->join('branches as fb', 'h.br_code', '=', 'fb.br_code')
            ->join('branches as tb', 'h.to_br_code', '=', 'tb.br_code')
            ->where('h.br_code', $brCode)
            ->whereBetween('h.iss_date', [$dateFrom, $dateTo])
            ->orderBy('h.iss_date', 'desc')
            ->orderBy('h.iss_no', 'desc')
            ->selectRaw('h.*, fb.br_name as from_br_name, tb.br_name as to_br_name,
                (SELECT COUNT(*) FROM branch_issue_dtl d WHERE d.iss_no = h.iss_no) as total_items')
            ->get();

        $branches = Branch::orderBy('br_name')->get();

        return view('transactions.stock-transfer.index', compact(
            'transfers', 'branches', 'dateFrom', 'dateTo', 'brCode'
        ));
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function create()
    {
        $brCode    = (int) session('br_code');
        $nextIssNo = $this->nextIssNo();
        $hdr       = null;
        $dtls      = collect();
        $firm      = Firm::first();

        $branches = Branch::where('br_code', '!=', $brCode)->orderBy('br_name')->get();
        $products = Product::orderBy('mat_name')->get();

        return view('transactions.stock-transfer.form', compact(
            'hdr', 'dtls', 'branches', 'products', 'nextIssNo', 'firm'
        ));
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->validateTransfer($request);

        DB::transaction(function () use ($request) {
            $fromBrCode = (int) session('br_code');
            $toBrCode   = (int) $request->to_br_code;
            $now        = now();

            $issNo = DB::table('branch_issue_hdr')->insertGetId([
                'iss_date'   => $request->iss_date,
                'br_code'    => $fromBrCode,
                'to_br_code' => $toBrCode,
                'created_at' => $now,
                'updated_at' => $now,
            ], 'iss_no');

            $slNo = 1;
            foreach ($request->items as $row) {
                $sentQty = (int) ($row['sent_qty'] ?? 0);
                $ordQty  = (int) ($row['order_qty'] ?? 0);

                DB::table('branch_issue_dtl')->insert([
                    'iss_no'    => $issNo,
                    'sl_no'     => $slNo++,
                    'br_code'   => $fromBrCode,
                    'item_code' => $row['item_code'],
                    'order_qty' => $ordQty,
                    'sent_qty'  => $sentQty,
                    'po_no'     => !empty($row['po_no']) ? (int) $row['po_no'] : null,
                    'created_at'=> $now,
                    'updated_at'=> $now,
                ]);

                // Update FROM branch stock: issues += sent_qty
                $fromStock = Stock::where('br_code', $fromBrCode)->where('mat_code', $row['item_code'])->first();
                if ($fromStock) {
                    $fromStock->issues  += $sentQty;
                    $fromStock->cl_stock = $fromStock->ob + $fromStock->rcpts - $fromStock->issues;
                    $fromStock->save();
                }

                // Update TO branch stock: rcpts += sent_qty
                $toStock = Stock::where('br_code', $toBrCode)->where('mat_code', $row['item_code'])->first();
                if ($toStock) {
                    $toStock->rcpts   += $sentQty;
                    $toStock->cl_stock = $toStock->ob + $toStock->rcpts - $toStock->issues;
                    $toStock->save();
                } else {
                    // Create stock record for TO branch if it doesn't exist
                    $product = Product::where('mat_code', $row['item_code'])->first();
                    if ($product) {
                        Stock::create([
                            'br_code'    => $toBrCode,
                            'mat_code'   => $row['item_code'],
                            'fin_year_id'=> (int) session('fin_year_id'),
                            'ob'         => 0,
                            'rcpts'      => $sentQty,
                            'issues'     => 0,
                            'cl_stock'   => $sentQty,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('transactions.stock-transfer.index')
                         ->with('success', 'Stock transfer saved successfully.');
    }

    // ── Edit ─────────────────────────────────────────────────────────────────

    public function edit($issNo)
    {
        $issNo = (int) $issNo;

        $hdr = DB::table('branch_issue_hdr as h')
            ->join('branches as tb', 'h.to_br_code', '=', 'tb.br_code')
            ->where('h.iss_no', $issNo)
            ->select('h.*', 'tb.br_name as to_br_name')
            ->first();

        abort_if(!$hdr, 404);

        $dtls = DB::table('branch_issue_dtl as d')
            ->join('products as p', 'd.item_code', '=', 'p.mat_code')
            ->where('d.iss_no', $issNo)
            ->orderBy('d.sl_no')
            ->select('d.*', 'p.mat_name')
            ->get();

        $brCode   = (int) session('br_code');
        $branches = Branch::where('br_code', '!=', $brCode)->orderBy('br_name')->get();
        $products = Product::orderBy('mat_name')->get();
        $firm     = Firm::first();
        $nextIssNo = $hdr->iss_no;

        return view('transactions.stock-transfer.form', compact(
            'hdr', 'dtls', 'branches', 'products', 'nextIssNo', 'firm'
        ));
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, $issNo)
    {
        $issNo = (int) $issNo;

        $hdr = DB::table('branch_issue_hdr')->where('iss_no', $issNo)->first();
        abort_if(!$hdr, 404);

        $this->validateTransfer($request);

        DB::transaction(function () use ($request, $issNo, $hdr) {
            $fromBrCode    = (int) $hdr->br_code;
            $oldToBrCode   = (int) $hdr->to_br_code;
            $newToBrCode   = (int) $request->to_br_code;
            $now           = now();

            // Reverse old stock changes
            $oldDtls = DB::table('branch_issue_dtl')->where('iss_no', $issNo)->get();
            foreach ($oldDtls as $od) {
                $oldSent = (int) $od->sent_qty;

                $fromStock = Stock::where('br_code', $fromBrCode)->where('mat_code', $od->item_code)->first();
                if ($fromStock) {
                    $fromStock->issues  -= $oldSent;
                    $fromStock->cl_stock = $fromStock->ob + $fromStock->rcpts - $fromStock->issues;
                    $fromStock->save();
                }

                $toStock = Stock::where('br_code', $oldToBrCode)->where('mat_code', $od->item_code)->first();
                if ($toStock) {
                    $toStock->rcpts   -= $oldSent;
                    $toStock->cl_stock = $toStock->ob + $toStock->rcpts - $toStock->issues;
                    $toStock->save();
                }
            }

            DB::table('branch_issue_dtl')->where('iss_no', $issNo)->delete();

            DB::table('branch_issue_hdr')->where('iss_no', $issNo)->update([
                'iss_date'   => $request->iss_date,
                'to_br_code' => $newToBrCode,
                'updated_at' => $now,
            ]);

            $slNo = 1;
            foreach ($request->items as $row) {
                $sentQty = (int) ($row['sent_qty'] ?? 0);
                $ordQty  = (int) ($row['order_qty'] ?? 0);

                DB::table('branch_issue_dtl')->insert([
                    'iss_no'    => $issNo,
                    'sl_no'     => $slNo++,
                    'br_code'   => $fromBrCode,
                    'item_code' => $row['item_code'],
                    'order_qty' => $ordQty,
                    'sent_qty'  => $sentQty,
                    'po_no'     => !empty($row['po_no']) ? (int) $row['po_no'] : null,
                    'created_at'=> $now,
                    'updated_at'=> $now,
                ]);

                $fromStock = Stock::where('br_code', $fromBrCode)->where('mat_code', $row['item_code'])->first();
                if ($fromStock) {
                    $fromStock->issues  += $sentQty;
                    $fromStock->cl_stock = $fromStock->ob + $fromStock->rcpts - $fromStock->issues;
                    $fromStock->save();
                }

                $toStock = Stock::where('br_code', $newToBrCode)->where('mat_code', $row['item_code'])->first();
                if ($toStock) {
                    $toStock->rcpts   += $sentQty;
                    $toStock->cl_stock = $toStock->ob + $toStock->rcpts - $toStock->issues;
                    $toStock->save();
                } else {
                    Stock::create([
                        'br_code'    => $newToBrCode,
                        'mat_code'   => $row['item_code'],
                        'fin_year_id'=> (int) session('fin_year_id'),
                        'ob'         => 0,
                        'rcpts'      => $sentQty,
                        'issues'     => 0,
                        'cl_stock'   => $sentQty,
                    ]);
                }
            }
        });

        return redirect()->route('transactions.stock-transfer.index')
                         ->with('success', 'Stock transfer updated successfully.');
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function destroy($issNo)
    {
        $issNo = (int) $issNo;

        $hdr = DB::table('branch_issue_hdr')->where('iss_no', $issNo)->first();
        abort_if(!$hdr, 404);

        DB::transaction(function () use ($issNo, $hdr) {
            $fromBrCode = (int) $hdr->br_code;
            $toBrCode   = (int) $hdr->to_br_code;

            $dtls = DB::table('branch_issue_dtl')->where('iss_no', $issNo)->get();
            foreach ($dtls as $d) {
                $sentQty = (int) $d->sent_qty;

                $fromStock = Stock::where('br_code', $fromBrCode)->where('mat_code', $d->item_code)->first();
                if ($fromStock) {
                    $fromStock->issues  -= $sentQty;
                    $fromStock->cl_stock = $fromStock->ob + $fromStock->rcpts - $fromStock->issues;
                    $fromStock->save();
                }

                $toStock = Stock::where('br_code', $toBrCode)->where('mat_code', $d->item_code)->first();
                if ($toStock) {
                    $toStock->rcpts   -= $sentQty;
                    $toStock->cl_stock = $toStock->ob + $toStock->rcpts - $toStock->issues;
                    $toStock->save();
                }
            }

            DB::table('branch_issue_dtl')->where('iss_no', $issNo)->delete();
            DB::table('branch_issue_hdr')->where('iss_no', $issNo)->delete();
        });

        return back()->with('success', 'Stock transfer deleted and stock reversed.');
    }

    // ── Print ─────────────────────────────────────────────────────────────────

    public function printTransfer($issNo)
    {
        $issNo = (int) $issNo;

        $hdr = DB::table('branch_issue_hdr as h')
            ->join('branches as fb', 'h.br_code', '=', 'fb.br_code')
            ->join('branches as tb', 'h.to_br_code', '=', 'tb.br_code')
            ->where('h.iss_no', $issNo)
            ->select('h.*',
                     'fb.br_name as from_br_name', 'fb.address as from_br_address',
                     'fb.place as from_br_place',
                     'tb.br_name as to_br_name', 'tb.address as to_br_address',
                     'tb.place as to_br_place')
            ->first();

        abort_if(!$hdr, 404);

        $dtls = DB::table('branch_issue_dtl as d')
            ->join('products as p', 'd.item_code', '=', 'p.mat_code')
            ->join('uoms as u', 'p.uom', '=', 'u.uom_code')
            ->where('d.iss_no', $issNo)
            ->orderBy('d.sl_no')
            ->select('d.*', 'p.mat_name', 'u.uom_name')
            ->get();

        $firm = Firm::first();

        return view('transactions.stock-transfer.print', compact('hdr', 'dtls', 'firm'));
    }

    // ── AJAX: get product info ─────────────────────────────────────────────────

    public function getProduct($matCode)
    {
        $product = DB::table('products as p')
            ->join('uoms as u', 'p.uom', '=', 'u.uom_code')
            ->where('p.mat_code', $matCode)
            ->select('p.mat_code', 'p.mat_name', 'u.uom_name')
            ->first();

        abort_if(!$product, 404);
        return response()->json($product);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function nextIssNo(): int
    {
        return (int) (DB::table('branch_issue_hdr')->max('iss_no') ?? 0) + 1;
    }

    private function validateTransfer(Request $request): void
    {
        $request->validate([
            'iss_date'           => 'required|date',
            'to_br_code'         => 'required|integer|exists:branches,br_code',
            'items'              => 'required|array|min:1',
            'items.*.item_code'  => 'required|string|exists:products,mat_code',
            'items.*.sent_qty'   => 'required|integer|min:1',
        ]);
    }
}
