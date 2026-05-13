<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\FinancialYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialYearController extends Controller
{
    public function index()
    {
        $years = FinancialYear::orderBy('start_date')->get();

        return view('utilities.financial-year.index', compact('years'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'year_name'  => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        $setActive = $request->boolean('is_active');

        if ($setActive) {
            FinancialYear::query()->update(['is_active' => 0]);
        }

        FinancialYear::create([
            'year_name'  => $request->year_name,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'is_active'  => $setActive ? 1 : 0,
        ]);

        return back()->with('success', 'Financial year "' . $request->year_name . '" added successfully.');
    }

    public function setActive($id)
    {
        FinancialYear::query()->update(['is_active' => 0]);
        FinancialYear::findOrFail($id)->update(['is_active' => 1]);

        return back()->with('success', 'Financial year set as active.');
    }

    public function copyClosingStock($id)
    {
        $year = FinancialYear::findOrFail($id);

        // Copy cl_stock → ob and reset movement columns for the new year
        DB::table('stock')
            ->where('br_code', session('br_code'))
            ->update([
                'ob'     => DB::raw('cl_stock'),
                'rcpts'  => 0,
                'issues' => 0,
            ]);

        return back()->with('success', 'Closing stock copied as opening balance for "' . $year->year_name . '".');
    }

    public function destroy($id)
    {
        $year = FinancialYear::findOrFail($id);

        if ($year->is_active) {
            return back()->with('error', 'Cannot delete the active financial year.');
        }

        $year->delete();

        return back()->with('success', 'Financial year deleted.');
    }
}
