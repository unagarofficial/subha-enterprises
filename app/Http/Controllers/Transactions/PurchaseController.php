<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Firm;
use App\Models\Party;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Tax;
use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    // ── List ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $dateFrom  = $request->get('date_from', session('login_date'));
        $dateTo    = $request->get('date_to', session('login_date'));
        $partyCode = $request->get('party_code', '');
        $brCode    = $request->get('br_code', session('br_code'));

        $query = DB::table('purchase_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->join('branches as b', 'h.br_code', '=', 'b.br_code')
            ->where('h.fin_year_id', session('fin_year_id'))
            ->where('h.br_code', $brCode)
            ->whereBetween('h.inv_date', [$dateFrom, $dateTo]);

        if ($partyCode) {
            $query->where('h.party_code', $partyCode);
        }

        $purchases = $query
            ->orderBy('h.inv_date', 'desc')
            ->orderBy('h.inv_no', 'desc')
            ->select('h.*', 'p.party_name', 'p.place as party_place', 'b.br_name')
            ->get();

        $suppliers = Party::suppliers()->orderBy('party_name')->get();
        $branches  = Branch::orderBy('br_name')->get();

        return view('transactions.purchase.index', compact(
            'purchases', 'suppliers', 'branches',
            'dateFrom', 'dateTo', 'partyCode', 'brCode'
        ));
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function create()
    {
        [$branches, $suppliers, $taxes, $categories, $uoms, $products] = $this->formData(session('br_code'));

        $nextInvNo = $this->nextInvNo(session('br_code'), session('fin_year_id'));
        $hdr       = null;
        $dtls      = collect();

        return view('transactions.purchase.form', compact(
            'hdr', 'dtls', 'branches', 'suppliers', 'taxes',
            'categories', 'uoms', 'products', 'nextInvNo'
        ));
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->validatePurchase($request);

        DB::transaction(function () use ($request) {
            $brCode = (int) $request->br_code;
            $invNo  = $this->nextInvNo($brCode, session('fin_year_id'));
            $now    = now();

            DB::table('purchase_hdr')->insert([
                'br_code'     => $brCode,
                'inv_no'      => $invNo,
                'inv_date'    => $request->inv_date,
                'party_code'  => $request->party_code,
                'gross'       => $request->gross,
                'tax_rate'    => $request->tax_rate,
                'tax_amount'  => $request->tax_amount,
                'nett'        => $request->nett,
                'fin_year_id' => session('fin_year_id'),
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $slNo = 1;
            foreach ($request->items as $row) {
                DB::table('purchase_dtl')->insert([
                    'br_code'    => $brCode,
                    'inv_no'     => $invNo,
                    'sl_no'      => $slNo++,
                    'mat_code'   => $row['mat_code'],
                    'qty'        => $row['qty'],
                    'uom'        => $row['uom'],
                    'rate'       => $row['rate'],
                    'amount'     => $row['amount'],
                    'narration'  => $row['narration'] ?? null,
                    'cat_code'   => $row['cat_code'],
                    'po_no'      => ($row['po_no'] ?? '') !== '' ? $row['po_no'] : null,
                    'inv_date'   => $request->inv_date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->stockIn($brCode, $row['mat_code'], (int)$row['cat_code'], (float)$row['qty']);
            }
        });

        return redirect()->route('transactions.purchase.index')
                         ->with('success', 'Purchase invoice saved successfully.');
    }

    // ── Edit ─────────────────────────────────────────────────────────────────

    public function edit($brCode, $invNo)
    {
        $hdr = DB::table('purchase_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->where('h.br_code', $brCode)->where('h.inv_no', $invNo)
            ->select('h.*', 'p.party_name')
            ->first();

        abort_if(!$hdr, 404);

        $dtls = DB::table('purchase_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->join('uoms as u', 'd.uom', '=', 'u.uom_code')
            ->where('d.br_code', $brCode)->where('d.inv_no', $invNo)
            ->orderBy('d.sl_no')
            ->select('d.*', 'p.mat_name', 'u.uom_name')
            ->get();

        [$branches, $suppliers, $taxes, $categories, $uoms, $products] = $this->formData($brCode);
        $nextInvNo = $hdr->inv_no;

        return view('transactions.purchase.form', compact(
            'hdr', 'dtls', 'branches', 'suppliers', 'taxes',
            'categories', 'uoms', 'products', 'nextInvNo'
        ));
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, $brCode, $invNo)
    {
        $this->validatePurchase($request);

        DB::transaction(function () use ($request, $brCode, $invNo) {
            $now = now();

            $oldRows = DB::table('purchase_dtl')
                ->where('br_code', $brCode)->where('inv_no', $invNo)->get();
            foreach ($oldRows as $old) {
                $this->stockOut($brCode, $old->mat_code, $old->cat_code, (float)$old->qty);
            }

            DB::table('purchase_dtl')->where('br_code', $brCode)->where('inv_no', $invNo)->delete();

            DB::table('purchase_hdr')
                ->where('br_code', $brCode)->where('inv_no', $invNo)
                ->update([
                    'inv_date'   => $request->inv_date,
                    'party_code' => $request->party_code,
                    'gross'      => $request->gross,
                    'tax_rate'   => $request->tax_rate,
                    'tax_amount' => $request->tax_amount,
                    'nett'       => $request->nett,
                    'updated_at' => $now,
                ]);

            $slNo = 1;
            foreach ($request->items as $row) {
                DB::table('purchase_dtl')->insert([
                    'br_code'    => $brCode,
                    'inv_no'     => $invNo,
                    'sl_no'      => $slNo++,
                    'mat_code'   => $row['mat_code'],
                    'qty'        => $row['qty'],
                    'uom'        => $row['uom'],
                    'rate'       => $row['rate'],
                    'amount'     => $row['amount'],
                    'narration'  => $row['narration'] ?? null,
                    'cat_code'   => $row['cat_code'],
                    'po_no'      => ($row['po_no'] ?? '') !== '' ? $row['po_no'] : null,
                    'inv_date'   => $request->inv_date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->stockIn($brCode, $row['mat_code'], (int)$row['cat_code'], (float)$row['qty']);
            }
        });

        return redirect()->route('transactions.purchase.index')
                         ->with('success', 'Purchase invoice updated successfully.');
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function destroy($brCode, $invNo)
    {
        DB::transaction(function () use ($brCode, $invNo) {
            $rows = DB::table('purchase_dtl')
                ->where('br_code', $brCode)->where('inv_no', $invNo)->get();
            foreach ($rows as $row) {
                $this->stockOut($brCode, $row->mat_code, $row->cat_code, (float)$row->qty);
            }
            DB::table('purchase_dtl')->where('br_code', $brCode)->where('inv_no', $invNo)->delete();
            DB::table('purchase_hdr')->where('br_code', $brCode)->where('inv_no', $invNo)->delete();
        });

        return back()->with('success', 'Purchase invoice deleted.');
    }

    // ── Print Invoice ─────────────────────────────────────────────────────────

    public function printInvoice($brCode, $invNo)
    {
        $hdr = DB::table('purchase_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->join('branches as b', 'h.br_code', '=', 'b.br_code')
            ->where('h.br_code', $brCode)->where('h.inv_no', $invNo)
            ->select('h.*', 'p.party_name', 'p.address as party_address', 'p.place as party_place',
                     'p.tin_grn_no', 'b.br_name')
            ->first();

        abort_if(!$hdr, 404);

        $dtls = DB::table('purchase_dtl as d')
            ->join('products as p', 'd.mat_code', '=', 'p.mat_code')
            ->join('uoms as u', 'd.uom', '=', 'u.uom_code')
            ->where('d.br_code', $brCode)->where('d.inv_no', $invNo)
            ->orderBy('d.sl_no')
            ->select('d.*', 'p.mat_name', 'u.uom_name')
            ->get();

        $firm = Firm::first();

        return view('transactions.purchase.print', compact('hdr', 'dtls', 'firm'));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function formData(int $brCode): array
    {
        return [
            Branch::orderBy('br_name')->get(),
            Party::suppliers()->orderBy('party_name')->get(),
            Tax::orderBy('tax_name')->get(),
            Category::orderBy('cat_name')->get(),
            Uom::orderBy('uom_name')->get(),
            Product::with(['uomUnit', 'category'])
                   ->where('br_code', $brCode)
                   ->orderBy('mat_name')
                   ->get(),
        ];
    }

    private function nextInvNo(int $brCode, int $finYearId): int
    {
        return (int)(DB::table('purchase_hdr')
            ->where('br_code', $brCode)
            ->where('fin_year_id', $finYearId)
            ->max('inv_no') ?? 0) + 1;
    }

    private function validatePurchase(Request $request): void
    {
        $request->validate([
            'br_code'          => 'required|integer|exists:branches,br_code',
            'inv_date'         => 'required|date',
            'party_code'       => 'required|integer|exists:parties,party_code',
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
            'items.*.cat_code' => 'required|integer|exists:categories,cat_code',
        ]);
    }

    private function stockIn(int $brCode, string $matCode, int $catCode, float $qty): void
    {
        $stock = Stock::where('br_code', $brCode)->where('mat_code', $matCode)->first();
        if ($stock) {
            $stock->rcpts   += $qty;
            $stock->cl_stock = $stock->ob + $stock->rcpts - $stock->issues;
            $stock->save();
        } else {
            Stock::create([
                'br_code'  => $brCode, 'mat_code' => $matCode, 'cat_code' => $catCode,
                'ob' => 0, 'rcpts' => $qty, 'issues' => 0, 'cl_stock' => $qty,
            ]);
        }
    }

    private function stockOut(int $brCode, string $matCode, int $catCode, float $qty): void
    {
        $stock = Stock::where('br_code', $brCode)->where('mat_code', $matCode)->first();
        if ($stock) {
            $stock->rcpts    = max(0, $stock->rcpts - $qty);
            $stock->cl_stock = $stock->ob + $stock->rcpts - $stock->issues;
            $stock->save();
        }
    }

    // ── Amount in Words (Indian System) ──────────────────────────────────────

    public static function numberToWords(float $amount): string
    {
        $n     = (int) abs($amount);
        $paise = (int) round((abs($amount) - $n) * 100);

        $words  = $n === 0 ? 'Zero' : trim(self::toWords($n));
        $result = $words . ' Rupees';

        if ($paise > 0) {
            $result .= ' and ' . trim(self::toWords($paise)) . ' Paise';
        }

        return $result . ' Only';
    }

    private static function toWords(int $n): string
    {
        static $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                        'Seventeen', 'Eighteen', 'Nineteen'];
        static $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if ($n === 0) return '';

        $w = '';
        if ($n >= 10000000) { $w .= self::toWords((int)($n / 10000000)) . ' Crore ';   $n %= 10000000; }
        if ($n >= 100000)   { $w .= self::toWords((int)($n / 100000))   . ' Lakh ';    $n %= 100000; }
        if ($n >= 1000)     { $w .= self::toWords((int)($n / 1000))     . ' Thousand '; $n %= 1000; }
        if ($n >= 100)      { $w .= $ones[(int)($n / 100)] . ' Hundred '; $n %= 100; }
        if ($n >= 20)       { $w .= $tens[(int)($n / 10)] . ' '; $n %= 10; }
        if ($n > 0)         { $w .= $ones[$n] . ' '; }

        return $w;
    }
}
