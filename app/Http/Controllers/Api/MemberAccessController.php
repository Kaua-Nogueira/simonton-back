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
    public function generateAccess(\Illuminate\Http\Request $request, Member $member)
    {
        if (!$member->email) {
            return response()->json(['message' => 'O membro precisa de um e-mail cadastrado para ter acesso digital.'], 422);
        }

        $user = User::firstOrNew(['email' => $member->email]);
        $user->member_id = $member->id;
        $user->name = $member->name;
        $user->role = 'Membro (Sistema)';

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|string|min:6|confirmed',
            ]);
            $user->password = Hash::make($request->password);
            $user->must_change_password = false;
            $user->save();
            $customMessage = 'Acesso configurado com sucesso com a senha fornecida.';
        } else {
            // Invalidate previous credentials and force secure activation/reset flow.
            $user->password = Hash::make(Str::random(32));
            $user->must_change_password = true;
            $user->save();
            $customMessage = 'Link de acesso gerado. Envie o link de ativação ao membro.';
        }

        $memberRole = Role::where('name', 'Membro (Sistema)')->first();
        if ($memberRole) {
            $user->roles()->sync([$memberRole->id]);
        }

        $token = Password::broker()->createToken($user);
        
        $frontendUrl = config('app.frontend_url');
        
        // Se a URL do frontend for localhost mas o app_url for um IP, tenta ajustar
        if (str_contains($frontendUrl, 'localhost') && !str_contains(config('app.url'), 'localhost')) {
            $frontendUrl = str_replace('localhost', parse_url(config('app.url'), PHP_URL_HOST), $frontendUrl);
        }

        $activationUrl = sprintf(
            '%s/ativar-acesso?token=%s&email=%s',
            rtrim($frontendUrl, '/'),
            urlencode($token),
            urlencode($user->email)
        );

        return response()->json([
            'message' => $customMessage,
            'activation' => [
                'email' => $user->email,
                'activation_url' => $activationUrl,
                'expires_in_minutes' => (int) config('auth.passwords.users.expire', 60),
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $member = $user->member;

        // Fallback: if relationship is null but id exists
        if (!$member && $user->member_id) {
            $member = Member::find($user->member_id);
        }

        return response()->json([
            'user' => $user,
            'member' => $member ? new \App\Http\Resources\MemberResource($member) : null
        ]);
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
