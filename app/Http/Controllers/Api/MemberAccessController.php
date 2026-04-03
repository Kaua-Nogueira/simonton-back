<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use App\Models\Role;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberAccessController extends Controller
{
    /**
     * Generate or recreate user access for a specific member.
     * Permission: members.generate-access
     */
    public function generateAccess(Member $member)
    {
        if (!$member->email) {
            return response()->json(['message' => 'O membro precisa de um e-mail cadastrado para ter acesso digital.'], 422);
        }

        $password = Str::random(8);
        
        $user = User::firstOrNew(['email' => $member->email]);
        $user->member_id = $member->id;
        $user->name = $member->name;
        $user->role = 'Membro (Sistema)';
        $user->password = Hash::make($password);
        $user->save();

        // Assign 'Membro (Sistema)' role for permissions
        $memberRole = Role::where('name', 'Membro (Sistema)')->first();
        if ($memberRole) {
            $user->roles()->sync([$memberRole->id]);
        }

        return response()->json([
            'message' => 'Acesso gerado com sucesso!',
            'credentials' => [
                'email' => $user->email,
                'password' => $password
            ]
        ]);
    }

    /**
     * Get the authenticated member's profile data.
     * Permission: members.view-profile
     */
    public function me(Request $request)
    {
        $member = $request->user()->member;
        
        if (!$member) {
            return response()->json(['message' => 'Membro não vinculado.'], 404);
        }

        return new \App\Http\Resources\MemberResource($member);
    }

    /**
     * Get the authenticated member's contributions (Transactions).
     * Permission: members.view-contributions
     */
    public function contributions(Request $request)
    {
        $user = $request->user();
        $member = $user->member;

        \Illuminate\Support\Facades\Log::info('Member Portal Access Attempt', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_member_id' => $user->member_id,
            'member_resolved' => !!$member
        ]);

        if (!$member && $user->member_id) {
            // Force resolve if relationship failed
            $member = Member::find($user->member_id);
            \Illuminate\Support\Facades\Log::warning('Member relation fallback used', ['member_id' => $user->member_id]);
        }

        if (!$member) {
            return response()->json(['message' => 'Membro não vinculado.'], 404);
        }

        $transactions = Transaction::where('member_id', $member->id)
            ->with(['category', 'costCenter'])
            ->orderBy('date', 'desc')
            ->get();
            
        return \App\Http\Resources\TransactionResource::collection($transactions);
    }
}
