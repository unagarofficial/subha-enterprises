<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('cat_code')->get();
        return view('masters.category.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cat_name' => 'required|string|max:100|unique:categories,cat_name',
        ]);

        Category::create(['cat_name' => strtoupper($request->cat_name)]);

        return redirect()->route('masters.category.index')
                         ->with('success', 'Category added successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cat_name' => 'required|string|max:100|unique:categories,cat_name,' . $id . ',cat_code',
        ]);

        $cat = Category::findOrFail($id);
        $cat->update(['cat_name' => strtoupper($request->cat_name)]);

        return redirect()->route('masters.category.index')
                         ->with('success', 'Category updated successfully.');
    }

    public function destroy($id)
    {
        $cat = Category::findOrFail($id);
        $cat->delete();

        return redirect()->route('masters.category.index')
                         ->with('success', 'Category deleted successfully.');
    }
}
