<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')],
        ]);

        Category::create($data);

        return back()->with('status', "Category {$data['name']} added.");
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)],
        ]);

        $category->update($data);

        return back()->with('status', 'Category renamed.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // Products keep existing; the foreign key is nulled by the migration.
        $category->delete();

        return back()->with('status', 'Category deleted, its products are now uncategorised.');
    }
}
