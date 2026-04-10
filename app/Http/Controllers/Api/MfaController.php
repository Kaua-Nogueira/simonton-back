<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Security\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MfaController extends Controller
{
    public function status(Request $request)
    {
        $user = $request->user();

        $mfaPassed = $request->hasSession() ? (bool) $request->session()->get('mfa_passed', false) : false;

        return response()->json([
            'required' => $user->hasCriticalAccess(),
            'enabled' => (bool) $user->mfa_enabled,
            'verified' => $mfaPassed,
            'backup_codes_left' => is_array($user->mfa_backup_codes) ? count($user->mfa_backup_codes) : 0,
        ]);
    }

    public function setup(Request $request, TotpService $totp)
    {
        $user = $request->user();

        if (!$user->hasCriticalAccess()) {
            return response()->json(['message' => 'MFA nao obrigatorio para este perfil.'], 403);
        }

        $secret = $totp->generateSecret();

        if (!$request->hasSession()) {
            return response()->json(['message' => 'Sessao nao disponivel para configuracao MFA.'], 409);
        }

        $request->session()->put('mfa_setup_secret', $secret);

        $provisioningUri = $totp->getProvisioningUri(
            config('app.name', 'Simonton'),
            $user->email,
            $secret
        );

        return response()->json([
            'otpauth_url' => $provisioningUri,
        ]);
    }

    public function enable(Request $request, TotpService $totp)
    {
        $validated = $request->validate([
            'code' => 'required|string|min:6|max:8',
        ]);

        $user = $request->user();
        if (!$request->hasSession()) {
            return response()->json(['message' => 'Sessao nao disponivel para configuracao MFA.'], 409);
        }

        $secret = $request->session()->get('mfa_setup_secret');

        if (!$secret) {
            return response()->json(['message' => 'Sessao de ativacao MFA expirada.'], 422);
        }

        if (!$totp->verifyCode($secret, $validated['code'])) {
            return response()->json(['message' => 'Codigo MFA invalido.'], 422);
        }

        $backupCodes = collect(range(1, 8))
            ->map(fn () => strtoupper(bin2hex(random_bytes(4))))
            ->values()
            ->all();

        $hashedBackupCodes = array_map(
            static fn (string $backupCode): string => Hash::make($backupCode),
            $backupCodes
        );

        $user->update([
            'mfa_enabled' => true,
            'mfa_secret' => $secret,
            'mfa_backup_codes' => $hashedBackupCodes,
            'mfa_confirmed_at' => now(),
        ]);

        $request->session()->forget('mfa_setup_secret');
        $request->session()->put('mfa_passed', true);

        return response()->json([
            'message' => 'MFA ativado com sucesso.',
            'backup_codes' => $backupCodes,
        ]);
    }

    public function verify(Request $request, TotpService $totp)
    {
        $validated = $request->validate([
            'code' => 'required|string|min:6|max:64',
        ]);

        $user = $request->user();

        if (!$user->mfa_enabled || !$user->mfa_secret) {
            return response()->json(['message' => 'MFA nao esta configurado para este usuario.'], 422);
        }

        $code = strtoupper(trim($validated['code']));

        $backupCodes = is_array($user->mfa_backup_codes) ? $user->mfa_backup_codes : [];
        $matchedIndex = $this->findMatchingBackupCodeIndex($backupCodes, $code);
        if ($matchedIndex !== null) {
            unset($backupCodes[$matchedIndex]);
            $remaining = array_values($backupCodes);
            $user->update(['mfa_backup_codes' => $remaining]);
            if ($request->hasSession()) {
                $request->session()->put('mfa_passed', true);
            }

            return response()->json([
                'message' => 'MFA validado com codigo de recuperacao.',
                'backup_codes_left' => count($remaining),
            ]);
        }

        if (!$totp->verifyCode($user->mfa_secret, $code)) {
            return response()->json(['message' => 'Codigo MFA invalido.'], 422);
        }

        if ($request->hasSession()) {
            $request->session()->put('mfa_passed', true);
        }

        return response()->json(['message' => 'MFA validado com sucesso.']);
    }

    public function regenerateBackupCodes(Request $request)
    {
        $user = $request->user();

        if (!$user->mfa_enabled) {
            return response()->json(['message' => 'MFA nao esta habilitado.'], 422);
        }

        $backupCodes = collect(range(1, 8))
            ->map(fn () => strtoupper(bin2hex(random_bytes(4))))
            ->values()
            ->all();

        $hashedBackupCodes = array_map(
            static fn (string $backupCode): string => Hash::make($backupCode),
            $backupCodes
        );

        $user->update(['mfa_backup_codes' => $hashedBackupCodes]);

        return response()->json([
            'message' => 'Novos codigos de recuperacao gerados.',
            'backup_codes' => $backupCodes,
        ]);
    }

    public function disable(Request $request, TotpService $totp)
    {
        $validated = $request->validate([
            'code' => 'required|string|min:6|max:64',
        ]);

        $user = $request->user();
        $code = strtoupper(trim($validated['code']));

        $isTotp = $user->mfa_secret ? $totp->verifyCode($user->mfa_secret, $code) : false;
        $backupCodes = is_array($user->mfa_backup_codes) ? $user->mfa_backup_codes : [];
        $isBackup = $this->findMatchingBackupCodeIndex($backupCodes, $code) !== null;

        if (!$isTotp && !$isBackup) {
            return response()->json(['message' => 'Codigo MFA invalido para desativacao.'], 422);
        }

        $user->update([
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'mfa_backup_codes' => null,
            'mfa_confirmed_at' => null,
        ]);

        if ($request->hasSession()) {
            $request->session()->forget('mfa_passed');
        }

        return response()->json(['message' => 'MFA desativado com sucesso.']);
    }

    private function findMatchingBackupCodeIndex(array $backupCodes, string $inputCode): ?int
    {
        foreach ($backupCodes as $index => $storedCode) {
            if (!is_string($storedCode)) {
                continue;
            }

            // Backward compatibility for previously stored plain backup codes.
            if (hash_equals($storedCode, $inputCode) || Hash::check($inputCode, $storedCode)) {
                return $index;
            }
        }

        return null;
    }
}
