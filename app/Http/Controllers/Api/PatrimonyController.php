<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatrimonyAsset;
use App\Models\PatrimonyCategory;
use App\Models\PatrimonyLocation;
use App\Services\PatrimonyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatrimonyController extends Controller
{
    protected $service;

    public function __construct(PatrimonyService $service)
    {
        $this->service = $service;
    }

    /**
     * List assets with filters.
     */
    public function index(Request $request)
    {
        $query = PatrimonyAsset::with(['category', 'location', 'activeLoan'])->active();

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('state')) {
            $query->where('state', $request->state);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('tombo', 'like', "%{$search}%");
            });
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * Store a new asset (individual or batch).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:patrimony_categories,id',
            'location_id' => 'required|exists:patrimony_locations,id',
            'state' => 'required|in:novo,bom,regular,ruim,inservivel',
            'acquisition_value' => 'nullable|numeric|min:0',
            'estimated_value' => 'required|numeric|min:0',
            'acquisition_date' => 'nullable|date',
            'responsible' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
            'batch_quantity' => 'nullable|integer|min:1|max:100',
        ]);

        $quantity = $validated['batch_quantity'] ?? 1;

        if ($quantity > 1) {
            $assets = $this->service->batchCreate($validated, $quantity);
            return response()->json([
                'message' => "{$quantity} itens cadastrados com sucesso.",
                'assets' => $assets
            ], 201);
        }

        $asset = $this->service->createAsset($validated);
        return response()->json($asset->load(['category', 'location']), 201);
    }

    /**
     * Show an asset.
     */
    public function show($id)
    {
        $asset = PatrimonyAsset::with(['category', 'location', 'movements.fromLocation', 'movements.toLocation', 'movements.user', 'loans.member'])
            ->findOrFail($id);
            
        return response()->json($asset);
    }

    /**
     * Update an asset.
     */
    public function update(Request $request, $id)
    {
        $asset = PatrimonyAsset::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'location_id' => 'exists:patrimony_locations,id',
            'state' => 'in:novo,bom,regular,ruim,inservivel',
            'acquisition_value' => 'nullable|numeric|min:0',
            'estimated_value' => 'numeric|min:0',
            'acquisition_date' => 'nullable|date',
            'responsible' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // If location changed, use service to move it (to record history)
        if (isset($validated['location_id']) && $validated['location_id'] != $asset->location_id) {
            $this->service->moveAsset($asset, $validated['location_id']);
            unset($validated['location_id']); // Already updated in moveAsset
        }

        $asset->update($validated);
        
        return response()->json($asset->load(['category', 'location']));
    }

    /**
     * Archive/Deactivate an asset (Formal Disposal Protocol).
     */
    public function destroy(Request $request, $id)
    {
        $asset = PatrimonyAsset::findOrFail($id);
        
        $validated = $request->validate([
            'disposal_reason' => 'required|string|max:255',
            'disposal_date' => 'required|date',
            'disposal_observations' => 'nullable|string',
        ]);

        $asset->update([
            'is_active' => false,
            'disposal_reason' => $validated['disposal_reason'],
            'disposal_date' => $validated['disposal_date'],
            'disposal_observations' => $validated['disposal_observations'] ?? $asset->observations,
        ]);
        
        return response()->json(['message' => 'Baixa de item realizada com sucesso.']);
    }

    /**
     * Move an asset manually.
     */
    public function move(Request $request, $id)
    {
        $asset = PatrimonyAsset::findOrFail($id);
        $validated = $request->validate([
            'destination_location_id' => 'required|exists:patrimony_locations,id',
            'date' => 'nullable|date',
        ]);

        $movement = $this->service->moveAsset($asset, $validated['destination_location_id'], $validated['date'] ?? null);
        
        return response()->json($movement->load(['fromLocation', 'toLocation']));
    }

    /**
     * Dispose multiple assets in batch.
     */
    public function batchDispose(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:patrimony_assets,id',
            'disposal_reason' => 'required|string|max:255',
            'disposal_date' => 'required|date',
            'disposal_observations' => 'nullable|string',
        ]);

        $this->service->batchDispose($validated['ids'], $validated);

        return response()->json(['message' => 'Baixa em lote realizada com sucesso.']);
    }

    /**
     * Categories Listing.
     */
    public function categories()
    {
        return response()->json(PatrimonyCategory::orderBy('name')->get());
    }

    /**
     * Store Category.
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'required|string|max:10|unique:patrimony_categories,prefix',
        ]);

        $category = PatrimonyCategory::create($validated);
        return response()->json($category, 201);
    }

    /**
     * Update Category.
     */
    public function updateCategory(Request $request, $id)
    {
        $category = PatrimonyCategory::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'required|string|max:10|unique:patrimony_categories,prefix,' . $id,
        ]);

        $category->update($validated);
        return response()->json($category);
    }

    /**
     * Delete Category.
     */
    public function destroyCategory($id)
    {
        $category = PatrimonyCategory::findOrFail($id);
        
        $assetsCount = $category->assets()->count();
        if ($assetsCount > 0) {
            return response()->json([
                'message' => "Não é possível excluir uma categoria que possui {$assetsCount} itens vinculados (ativos ou baixados). Mova ou exclua os itens primeiro."
            ], 422);
        }

        $category->delete();
        return response()->json(['message' => 'Categoria excluída com sucesso.']);
    }

    /**
     * Locations Listing.
     */
    public function locations()
    {
        return response()->json(PatrimonyLocation::orderBy('name')->get());
    }

    /**
     * Store Location.
     */
    public function storeLocation(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $location = PatrimonyLocation::create($validated);
        return response()->json($location, 201);
    }

    /**
     * Update Location.
     */
    public function updateLocation(Request $request, $id)
    {
        $location = PatrimonyLocation::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $location->update($validated);
        return response()->json($location);
    }

    /**
     * Delete Location.
     */
    public function destroyLocation($id)
    {
        $location = PatrimonyLocation::findOrFail($id);
        
        // Check if there are assets in this location
        $assetsCount = PatrimonyAsset::where('location_id', $id)->count();
        if ($assetsCount > 0) {
            return response()->json([
                'message' => "Não é possível excluir um local que possui {$assetsCount} itens vinculados."
            ], 422);
        }

        $location->delete();
        return response()->json(['message' => 'Local excluído com sucesso.']);
    }

    /**
     * Dashboard Summary.
     */
    public function dashboard()
    {
        $stats = [
            'total_items' => PatrimonyAsset::active()->count(),
            'total_value' => PatrimonyAsset::active()->sum('estimated_value'),
            'by_category' => PatrimonyCategory::withCount('assets')->get()->map(function($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'count' => $cat->assets_count,
                    'value' => PatrimonyAsset::active()->where('category_id', $cat->id)->sum('estimated_value')
                ];
            }),
            'by_state' => PatrimonyAsset::active()
                ->select('state', DB::raw('count(*) as count'))
                ->groupBy('state')
                ->get()
        ];

        return response()->json($stats);
    }
}
