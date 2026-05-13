<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Category;
use App\Models\Branch;
use App\Models\FinancialYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $brCode  = $request->get('br_code', session('br_code'));
        $catCode = $request->get('cat_code', '');
        $finYear = FinancialYear::find(session('fin_year_id'));

        $query = Stock::with(['product.category', 'product.uomUnit'])
                      ->where('br_code', $brCode);

        if ($catCode) {
            $query->where('cat_code', $catCode);
        }

        $stocks = $query->orderBy('mat_code')->get();

        // Calculate receipts and issues from transactions for current fin year
        if ($finYear) {
            $stocks->each(function ($stock) use ($finYear) {
                $stock->calc_rcpts = DB::table('purchase_dtl')
                    ->where('mat_code', $stock->mat_code)
                    ->where('br_code', $stock->br_code)
                    ->whereBetween('inv_date', [$finYear->start_date, $finYear->end_date])
                    ->sum('qty') ?? 0;

                $stock->calc_issues = DB::table('sale_dtl')
                    ->where('mat_code', $stock->mat_code)
                    ->where('br_code', $stock->br_code)
                    ->whereBetween('inv_date', [$finYear->start_date, $finYear->end_date])
                    ->sum('qty') ?? 0;

                $stock->calc_closing = $stock->ob + $stock->calc_rcpts - $stock->calc_issues;
            });
        }

        $categories = Category::orderBy('cat_name')->get();
        $branches   = Branch::orderBy('br_name')->get();
        // All products for the add modal dropdown
        $products   = Product::with('category')
                             ->where('br_code', $brCode)
                             ->orderBy('mat_name')
                             ->get();

        return view('masters.stock.index', compact(
            'stocks', 'categories', 'branches', 'products', 'brCode', 'catCode', 'finYear'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'br_code'  => 'required|integer|exists:branches,br_code',
            'mat_code' => 'required|string|exists:products,mat_code',
            'ob'       => 'required|numeric|min:0',
        ]);

        $product = Product::findOrFail($request->mat_code);

        // Check duplicate
        $exists = Stock::where('br_code', $request->br_code)
                       ->where('mat_code', $request->mat_code)
                       ->exists();

        if ($exists) {
            return back()->with('error', "Opening balance for '{$product->mat_name}' already exists for this branch. Use Edit to update it.");
        }

        Stock::create([
            'br_code'  => $request->br_code,
            'mat_code' => $request->mat_code,
            'cat_code' => $product->cat_code,
            'ob'       => $request->ob,
            'rcpts'    => 0,
            'issues'   => 0,
            'cl_stock' => $request->ob,
        ]);

        return redirect()->route('masters.stock.index', ['br_code' => $request->br_code])
                         ->with('success', 'Opening balance saved successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ob' => 'required|numeric|min:0',
        ]);

        $stock = Stock::findOrFail($id);
        $stock->update(['ob' => $request->ob]);

        return redirect()->route('masters.stock.index', ['br_code' => $stock->br_code])
                         ->with('success', 'Opening balance updated successfully.');
    }

    public function destroy($id)
    {
        $stock = Stock::findOrFail($id);
        $stock->delete();

        return redirect()->route('masters.stock.index')
                         ->with('success', 'Stock entry removed.');
    }

    // AJAX: get product info by mat_code for auto-fill
    public function getProduct($matCode)
    {
        $product = Product::with('category')->find($matCode);
        if (!$product) return response()->json(null, 404);

        return response()->json([
            'mat_name' => $product->mat_name,
            'cat_name' => $product->category->cat_name ?? '',
            'cat_code' => $product->cat_code,
        ]);
    }
}
