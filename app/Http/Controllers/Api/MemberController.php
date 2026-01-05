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
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $this->authorize('viewAny', Member::class);

        $query = Member::with(['roles', 'user'])->withCount('transactions');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $members = $query->orderBy('name')->get();
        
        return response()->json(MemberResource::collection($members));
    }

    public function show(Member $member): JsonResponse
    {
        $this->authorize('view', $member);
        $member->load('transactions');
        
        return response()->json(new MemberResource($member));
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $this->authorize('create', Member::class);
        try {
            $data = $request->validated();
            
            // Auto-increment roll_number if not provided but requested or default?
            // IPB logic: usually sequential. If user leaves empty, we generate next.
            if (empty($data['roll_number'])) {
                $maxRoll = Member::max('roll_number');
                $data['roll_number'] = $maxRoll ? $maxRoll + 1 : 1;
            }

            // Remove role_id from data before creation
            $memberData = \Illuminate\Support\Arr::except($data, ['role_id']);
            $member = Member::create($memberData);

            if (!empty($data['role_id'])) {
                $member->roles()->attach($data['role_id'], [
                    'start_date' => now(),
                ]);
            }

            return response()->json([
                'message' => 'Member created successfully',
                'data' => new MemberResource($member),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating member: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function update(UpdateMemberRequest $request, Member $member): JsonResponse
    {
        $this->authorize('update', $member);

        $member->update($request->validated());

        return response()->json([
            'message' => 'Member updated successfully',
            'data' => new MemberResource($member->fresh()),
        ]);
    }

    public function transferLetter(Member $member)
    {
        $this->authorize('view', $member);
        return view('reports.transfer-letter', compact('member'));
    }
}
