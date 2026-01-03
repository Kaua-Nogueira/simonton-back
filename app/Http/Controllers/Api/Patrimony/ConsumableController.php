<?php

namespace App\Http\Controllers\Api\Patrimony;

use App\Http\Controllers\Controller;
use App\Models\Consumable;
use Illuminate\Http\Request;

class ConsumableController extends Controller
{
    public function index()
    {
        return response()->json(Consumable::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'unit' => 'required|string',
            'current_quantity' => 'integer',
            'min_threshold' => 'integer',
        ]);

        $consumable = Consumable::create($validated);
        return response()->json($consumable, 201);
    }

    public function update(Request $request, Consumable $consumable)
    {
        $validated = $request->validate([
            'name' => 'string',
            'unit' => 'string',
            'current_quantity' => 'integer',
            'min_threshold' => 'integer',
        ]);

        $consumable->update($validated);
        return response()->json($consumable);
    }
    
    public function destroy(Consumable $consumable)
    {
        $consumable->delete();
        return response()->noContent();
    }
}
