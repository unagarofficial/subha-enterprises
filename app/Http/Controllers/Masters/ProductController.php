<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Uom;
use App\Models\Branch;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $catCode  = $request->get('cat_code', '');
        $brCode   = $request->get('br_code', session('br_code'));

        $query = Product::with(['category', 'uomUnit', 'branch']);

        if ($catCode) $query->where('cat_code', $catCode);
        if ($brCode)  $query->where('br_code', $brCode);

        $products   = $query->orderBy('mat_code')->get();
        $categories = Category::orderBy('cat_name')->get();
        $branches   = Branch::orderBy('br_name')->get();

        return view('masters.product.index', compact(
            'products', 'categories', 'branches', 'catCode', 'brCode'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cat_code'  => 'required|integer|exists:categories,cat_code',
            'mat_code'  => 'required|string|max:50|unique:products,mat_code|regex:/^[A-Za-z0-9\-_\.]+$/',
            'mat_name'  => 'required|string|max:100',
            'uom'       => 'required|integer|exists:uoms,uom_code',
            'sale_rate' => 'nullable|numeric|min:0',
            'y_rate'    => 'nullable|numeric|min:0',
            'b_rate'    => 'nullable|numeric|min:0',
            'br_code'   => 'required|integer|exists:branches,br_code',
        ]);

        Product::create([
            'cat_code'  => $request->cat_code,
            'mat_code'  => strtoupper($request->mat_code),
            'mat_name'  => $request->mat_name,
            'uom'       => $request->uom,
            'sale_rate' => $request->sale_rate ?? 0,
            'y_rate'    => $request->y_rate ?? 0,
            'b_rate'    => $request->b_rate ?? 0,
            'br_code'   => $request->br_code,
        ]);

        return redirect()->route('masters.product.index', [
            'cat_code' => $request->cat_code,
            'br_code'  => $request->br_code,
        ])->with('success', 'Product added successfully.');
    }

    public function update(Request $request, $matCode)
    {
        $product = Product::findOrFail($matCode);

        $request->validate([
            'cat_code'  => 'required|integer|exists:categories,cat_code',
            'mat_name'  => 'required|string|max:100',
            'uom'       => 'required|integer|exists:uoms,uom_code',
            'sale_rate' => 'nullable|numeric|min:0',
            'y_rate'    => 'nullable|numeric|min:0',
            'b_rate'    => 'nullable|numeric|min:0',
            'br_code'   => 'required|integer|exists:branches,br_code',
        ]);

        $product->update([
            'cat_code'  => $request->cat_code,
            'mat_name'  => $request->mat_name,
            'uom'       => $request->uom,
            'sale_rate' => $request->sale_rate ?? 0,
            'y_rate'    => $request->y_rate ?? 0,
            'b_rate'    => $request->b_rate ?? 0,
            'br_code'   => $request->br_code,
        ]);

        return redirect()->route('masters.product.index', [
            'cat_code' => $product->cat_code,
            'br_code'  => $product->br_code,
        ])->with('success', 'Product updated successfully.');
    }

    public function destroy($matCode)
    {
        $product = Product::findOrFail($matCode);

        $usedIn = [];
        if (\DB::table('purchase_dtl')->where('mat_code', $matCode)->exists()) $usedIn[] = 'Purchase';
        if (\DB::table('sale_dtl')->where('mat_code', $matCode)->exists())     $usedIn[] = 'Sale';
        if (\DB::table('order_dtl')->where('mat_code', $matCode)->exists())    $usedIn[] = 'Order';
        if (\DB::table('stock')->where('mat_code', $matCode)->exists())        $usedIn[] = 'Stock';

        if (!empty($usedIn)) {
            return back()->with('error', "Cannot delete '{$product->mat_name}'. Used in: " . implode(', ', $usedIn) . '.');
        }

        $product->delete();
        return redirect()->route('masters.product.index')
                         ->with('success', 'Product deleted successfully.');
    }
}
