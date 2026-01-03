<?php

namespace App\Http\Controllers\Api\Patrimony;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::with(['category', 'location']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return response()->json($query->orderBy('name')->paginate(20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:assets',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:asset_categories,id',
            'location_id' => 'nullable|exists:locations,id',
            'status' => 'required|in:new,good,needs_repair,unusable,disposed',
            'purchase_date' => 'nullable|date',
            'purchase_value' => 'nullable|numeric',
            'invoice_number' => 'nullable|string',
            'supplier' => 'nullable|string',
            'image_url' => 'nullable|string',
        ]);

        $asset = Asset::create($validated);
        return response()->json($asset->load(['category', 'location']), 201);
    }

    public function show(Asset $asset)
    {
        return response()->json($asset->load(['category', 'location', 'maintenanceRequests.assignedTo', 'loans.member']));
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'code' => 'nullable|string|unique:assets,code,' . $asset->id,
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:asset_categories,id',
            'location_id' => 'nullable|exists:locations,id',
            'status' => 'in:new,good,needs_repair,unusable,disposed',
            'purchase_date' => 'nullable|date',
            'purchase_value' => 'nullable|numeric',
            'invoice_number' => 'nullable|string',
            'supplier' => 'nullable|string',
            'image_url' => 'nullable|string',
        ]);

        $asset->update($validated);
        return response()->json($asset->load(['category', 'location']));
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return response()->noContent();
    }
}
