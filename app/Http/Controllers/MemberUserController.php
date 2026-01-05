<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MemberUserController extends Controller
{
    /**
     * Store a newly created user linked to a member.
     */
    public function store(Request $request, Member $member)
    {
        if (empty($member->email)) {
            return response()->json(['message' => 'O membro precisa ter um email cadastrado para acessar o sistema.'], 422);
        }

        if (User::where('email', $member->email)->exists()) {
            return response()->json(['message' => 'Este email já está sendo usado por outro usuário.'], 422);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id']
        ]);

        $user = User::create([
            'name' => $member->name,
            'email' => $member->email,
            'password' => Hash::make($validated['password']),
            'role' => 'viewer', // Legacy fallback
            'member_id' => $member->id,
        ]);

        if (!empty($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }

        return response()->json([
            'message' => 'Acesso ao sistema concedido com sucesso!',
            'user' => $user->load('roles')
        ], 201);
    }

    public function update(Request $request, Member $member)
    {
        $user = $member->user;

        if (!$user) {
            return response()->json(['message' => 'Este membro não possui acesso ao sistema.'], 404);
        }

        $validated = $request->validate([
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id']
        ]);

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
            $user->save();
        }

        if ($request->has('roles')) {
            $user->roles()->sync($validated['roles']);
        }

        return response()->json([
            'message' => 'Acesso atualizado com sucesso!',
            'user' => $user->load('roles')
        ]);
    }
    
    /**
    * Revoke access 
    */
    public function destroy(Member $member)
    {
        $user = $member->user;
        
        if ($user) {
            $user->delete();
            return response()->json(['message' => 'Acesso revogado com sucesso.']);
        }
        
        return response()->json(['message' => 'Usuário não encontrado.'], 404);
    }

}
