<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Firm;
use Illuminate\Http\Request;

class FirmController extends Controller
{
    public function index()
    {
        $firm = Firm::first();
        return view('masters.firm.index', compact('firm'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'firm_name' => 'required|string|max:100',
            'place'     => 'required|string|max:100',
            'phone'     => 'nullable|digits_between:1,15',
            'mobile'    => 'nullable|digits_between:1,15',
            'tin_no'    => 'nullable|string|max:15',
            'type'      => 'required|in:H,B',
        ]);

        Firm::create($request->only([
            'firm_name', 'address', 'place', 'phone', 'mobile',
            'website', 'tin_no', 'ho_code', 'type',
        ]));

        return redirect()->route('masters.firm.index')
                         ->with('success', 'Firm information saved successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'firm_name' => 'required|string|max:100',
            'place'     => 'required|string|max:100',
            'phone'     => 'nullable|digits_between:1,15',
            'mobile'    => 'nullable|digits_between:1,15',
            'tin_no'    => 'nullable|string|max:15',
            'type'      => 'required|in:H,B',
        ]);

        $firm = Firm::findOrFail($id);
        $firm->update($request->only([
            'firm_name', 'address', 'place', 'phone', 'mobile',
            'website', 'tin_no', 'ho_code', 'type',
        ]));

        return redirect()->route('masters.firm.index')
                         ->with('success', 'Firm information updated successfully.');
    }
}
