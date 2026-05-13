<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function index()
    {
        $taxes = Tax::orderBy('tax_code')->get();
        return view('masters.tax.index', compact('taxes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tax_name'    => 'required|string|max:100',
            'tax_percent' => 'required|numeric|min:0|max:100',
        ]);

        Tax::create($request->only(['tax_name', 'tax_percent']));

        return redirect()->route('masters.tax.index')
                         ->with('success', 'Tax added successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tax_name'    => 'required|string|max:100',
            'tax_percent' => 'required|numeric|min:0|max:100',
        ]);

        $tax = Tax::findOrFail($id);
        $tax->update($request->only(['tax_name', 'tax_percent']));

        return redirect()->route('masters.tax.index')
                         ->with('success', 'Tax updated successfully.');
    }

    public function destroy($id)
    {
        $tax = Tax::findOrFail($id);
        $tax->delete();

        return redirect()->route('masters.tax.index')
                         ->with('success', 'Tax deleted successfully.');
    }
}
