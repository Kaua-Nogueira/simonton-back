<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CostCenterResource;
use App\Models\CostCenter;
use App\Http\Requests\StoreCostCenterRequest;
use App\Http\Requests\UpdateCostCenterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CostCenterController extends Controller
{
    public function index(): JsonResponse
    {
        $costCenters = CostCenter::all();

        return response()->json(CostCenterResource::collection($costCenters));
    }
    public function store(StoreCostCenterRequest $request): JsonResponse
    {
        $costCenter = CostCenter::create($request->validated());
        return response()->json(new CostCenterResource($costCenter), 201);
    }

    public function show(CostCenter $costCenter): JsonResponse
    {
        return response()->json(new CostCenterResource($costCenter));
    }

    public function update(UpdateCostCenterRequest $request, CostCenter $costCenter): JsonResponse
    {
        $costCenter->update($request->validated());
        return response()->json(new CostCenterResource($costCenter));
    }

    public function destroy(CostCenter $costCenter): JsonResponse
    {
        $costCenter->delete();
        return response()->json(null, 204);
    }
}
