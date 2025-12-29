<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CostCenterResource;
use App\Models\CostCenter;
use Illuminate\Http\JsonResponse;

class CostCenterController extends Controller
{
    public function index(): JsonResponse
    {
        $costCenters = CostCenter::all();

        return response()->json(CostCenterResource::collection($costCenters));
    }
}
