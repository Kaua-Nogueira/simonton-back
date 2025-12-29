<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use Illuminate\Http\JsonResponse;

class MemberController extends Controller
{
    public function index(): JsonResponse
    {
        $members = Member::withCount('transactions')->get();
        
        return response()->json(MemberResource::collection($members));
    }

    public function show(Member $member): JsonResponse
    {
        $member->load('transactions');
        
        return response()->json(new MemberResource($member));
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $member = Member::create($request->validated());

        return response()->json([
            'message' => 'Member created successfully',
            'data' => new MemberResource($member),
        ], 201);
    }

    public function update(UpdateMemberRequest $request, Member $member): JsonResponse
    {
        $member->update($request->validated());

        return response()->json([
            'message' => 'Member updated successfully',
            'data' => new MemberResource($member->fresh()),
        ]);
    }
}
