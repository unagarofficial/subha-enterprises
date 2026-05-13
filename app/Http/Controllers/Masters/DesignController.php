<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Category;
use App\Models\Uom;
use Illuminate\Http\Request;

class DesignController extends Controller
{
    public function index(Request $request)
    {
        $catCode  = $request->get('cat_code', '');

        $query = Design::with(['category', 'uomUnit']);
        if ($catCode) $query->where('cat_code', $catCode);

        $designs    = $query->orderBy('design_code')->get();
        $categories = Category::orderBy('cat_name')->get();

        return view('masters.design.index', compact('designs', 'categories', 'catCode'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cat_code'    => 'required|integer|exists:categories,cat_code',
            'design_code' => 'required|string|max:50',
            'design_desc' => 'required|string|max:255',
            'uom'         => 'required|integer|exists:uoms,uom_code',
            'rate'        => 'nullable|numeric|min:0',
            'y_rate'      => 'nullable|numeric|min:0',
            'b_rate'      => 'nullable|numeric|min:0',
        ]);

        Design::create([
            'cat_code'    => $request->cat_code,
            'design_code' => strtoupper($request->design_code),
            'design_desc' => $request->design_desc,
            'uom'         => $request->uom,
            'rate'        => $request->rate ?? 0,
            'y_rate'      => $request->y_rate ?? 0,
            'b_rate'      => $request->b_rate ?? 0,
        ]);

        return redirect()->route('masters.design.index', ['cat_code' => $request->cat_code])
                         ->with('success', 'Design added successfully.');
    }

    public function update(Request $request, $id)
    {
        $design = Design::findOrFail($id);

        $request->validate([
            'cat_code'    => 'required|integer|exists:categories,cat_code',
            'design_code' => 'required|string|max:50',
            'design_desc' => 'required|string|max:255',
            'uom'         => 'required|integer|exists:uoms,uom_code',
            'rate'        => 'nullable|numeric|min:0',
            'y_rate'      => 'nullable|numeric|min:0',
            'b_rate'      => 'nullable|numeric|min:0',
        ]);

        $design->update([
            'cat_code'    => $request->cat_code,
            'design_code' => strtoupper($request->design_code),
            'design_desc' => $request->design_desc,
            'uom'         => $request->uom,
            'rate'        => $request->rate ?? 0,
            'y_rate'      => $request->y_rate ?? 0,
            'b_rate'      => $request->b_rate ?? 0,
        ]);

        return redirect()->route('masters.design.index', ['cat_code' => $design->cat_code])
                         ->with('success', 'Design updated successfully.');
    }

    public function destroy($id)
    {
        $design = Design::findOrFail($id);
        $design->delete();
        return redirect()->route('masters.design.index')
                         ->with('success', 'Design deleted successfully.');
    }
}
