<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Uom;
use Illuminate\Http\Request;

class UomController extends Controller
{
    public function index()
    {
        $uoms = Uom::orderBy('uom_code')->get();
        return view('masters.uom.index', compact('uoms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'uom_name' => 'required|string|max:20|unique:uoms,uom_name',
        ]);

        Uom::create(['uom_name' => strtoupper($request->uom_name)]);

        return redirect()->route('masters.uom.index')
                         ->with('success', 'UOM added successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'uom_name' => 'required|string|max:20|unique:uoms,uom_name,' . $id . ',uom_code',
        ]);

        $uom = Uom::findOrFail($id);
        $uom->update(['uom_name' => strtoupper($request->uom_name)]);

        return redirect()->route('masters.uom.index')
                         ->with('success', 'UOM updated successfully.');
    }

    public function destroy($id)
    {
        $uom = Uom::findOrFail($id);
        $uom->delete();

        return redirect()->route('masters.uom.index')
                         ->with('success', 'UOM deleted successfully.');
    }
}
