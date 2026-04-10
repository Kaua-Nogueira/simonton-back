<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            /** @var User $authUser */
            $authUser = Auth::user();
            $requiresMfa = $authUser->hasCriticalAccess();
            $enrollmentRequired = $requiresMfa && !$authUser->mfa_enabled;
            $mfaRequired = $requiresMfa && $authUser->mfa_enabled;

            if ($request->hasSession()) {
                $request->session()->put('mfa_passed', !$mfaRequired);
            }

            return response()->json([
                'message' => 'Login successful',
                'user' => $authUser,
                'must_change_password' => (bool) $authUser->must_change_password,
                'mfa_required' => $mfaRequired,
                'mfa_enrollment_required' => $enrollmentRequired,
            ]);
        }

        throw ValidationException::withMessages([
            'email' => ['As credenciais fornecidas estao incorretas.'],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        $user = $request->user()->load(['member', 'roles.permissions', 'permissions']);

        // Flatten permissions for easier frontend consumption
        $allPermissions = $user->getAllPermissions()->pluck('name');

        $userData = $user->toArray();
        $userData['all_permissions'] = $allPermissions;

        // Ensure 'role' is populated for frontend logic if missing on database column
        if (empty($userData['role']) && $user->roles->count() > 0) {
            $userData['role'] = $user->roles->first()->name;
        }

        $userData['must_change_password'] = (bool) $user->must_change_password;
        $userData['mfa_enabled'] = (bool) $user->mfa_enabled;
        $mfaPassed = $request->hasSession() ? (bool) $request->session()->get('mfa_passed', false) : false;
        $userData['mfa_required'] = $user->hasCriticalAccess() && $user->mfa_enabled && !$mfaPassed;
        $userData['mfa_enrollment_required'] = $user->hasCriticalAccess() && !$user->mfa_enabled;

        return response()->json($userData);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => [
                'required',
                'confirmed',
                \Illuminate\Validation\Rules\Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'A senha atual esta incorreta.'], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return response()->json(['message' => 'Senha atualizada com sucesso.']);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        // Always return a generic response to avoid user enumeration.
        Password::sendResetLink(['email' => $validated['email']]);

        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, voce recebera as instrucoes de redefinicao.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => [
                'required',
                'confirmed',
                \Illuminate\Validation\Rules\Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'must_change_password' => false,
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 422);
        }

        return response()->json(['message' => 'Senha redefinida com sucesso.']);
    }
}
