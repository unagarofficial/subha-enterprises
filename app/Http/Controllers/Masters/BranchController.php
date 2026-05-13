<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::orderBy('br_code')->get();
        return view('masters.branch.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'br_name'  => 'required|string|max:100|unique:branches,br_name',
            'br_place' => 'nullable|string|max:100',
        ]);

        Branch::create($request->only(['br_name', 'br_place']));

        return redirect()->route('masters.branch.index')
                         ->with('success', 'Branch added successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'br_name'  => 'required|string|max:100|unique:branches,br_name,' . $id . ',br_code',
            'br_place' => 'nullable|string|max:100',
        ]);

        $branch = Branch::findOrFail($id);
        $branch->update($request->only(['br_name', 'br_place']));

        return redirect()->route('masters.branch.index')
                         ->with('success', 'Branch updated successfully.');
    }

    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);

        // Prevent deleting branch currently in session
        if ($id == session('br_code')) {
            return redirect()->route('masters.branch.index')
                             ->with('error', 'Cannot delete the branch you are currently logged in to.');
        }

        $branch->delete();

        return redirect()->route('masters.branch.index')
                         ->with('success', 'Branch deleted successfully.');
    }
}
