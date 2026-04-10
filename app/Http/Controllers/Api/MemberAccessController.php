<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class MemberAccessController extends Controller
{
    /**
     * Generate or recreate member portal access with one-time activation link.
     */
    public function generateAccess(Member $member)
    {
        if (!$member->email) {
            return response()->json(['message' => 'O membro precisa de um e-mail cadastrado para ter acesso digital.'], 422);
        }

        $user = User::firstOrNew(['email' => $member->email]);
        $user->member_id = $member->id;
        $user->name = $member->name;
        $user->role = 'Membro (Sistema)';

        // Invalidate previous credentials and force secure activation/reset flow.
        $user->password = Hash::make(Str::random(32));
        $user->must_change_password = true;
        $user->save();

        $memberRole = Role::where('name', 'Membro (Sistema)')->first();
        if ($memberRole) {
            $user->roles()->sync([$memberRole->id]);
        }

        $token = Password::broker()->createToken($user);
        $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        $activationUrl = sprintf(
            '%s/ativar-acesso?token=%s&email=%s',
            $frontendUrl,
            urlencode($token),
            urlencode($user->email)
        );

        return response()->json([
            'message' => 'Acesso gerado com sucesso. Envie o link de ativacao ao membro.',
            'activation' => [
                'email' => $user->email,
                'activation_url' => $activationUrl,
                'expires_in_minutes' => (int) config('auth.passwords.users.expire', 60),
            ],
        ]);
    }

    public function me(Request $request)
    {
        $member = $request->user()->member;

        if (!$member) {
            return response()->json(['message' => 'Membro nao vinculado.'], 404);
        }

        return new \App\Http\Resources\MemberResource($member);
    }

    public function contributions(Request $request)
    {
        $user = $request->user();
        $member = $user->member;

        if (!$member && $user->member_id) {
            $member = Member::find($user->member_id);
        }

        if (!$member) {
            return response()->json(['message' => 'Membro nao vinculado.'], 404);
        }

        $transactions = Transaction::where('member_id', $member->id)
            ->with(['category', 'costCenter'])
            ->orderBy('date', 'desc')
            ->paginate(50);

        return \App\Http\Resources\TransactionResource::collection($transactions);
    }
}
