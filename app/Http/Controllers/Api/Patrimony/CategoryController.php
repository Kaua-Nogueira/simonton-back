<?php

namespace App\Http\Controllers\Api\Patrimony;

use App\Http\Controllers\Controller;
use App\Models\AssetCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(AssetCategory::with('parent')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:asset_categories,id',
        ]);

        $category = AssetCategory::create($validated);
        return response()->json($category, 201);
    }

    public function show(AssetCategory $category)
    {
        return response()->json($category->load('children'));
    }

    public function update(Request $request, AssetCategory $category)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'parent_id' => 'nullable|exists:asset_categories,id',
        ]);

        $category->update($validated);
        return response()->json($category);
    }

    public function destroy(AssetCategory $category)
    {
        $category->delete();
        return response()->noContent();
    }
}
