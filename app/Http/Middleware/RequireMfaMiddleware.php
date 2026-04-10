<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMfaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->hasCriticalAccess()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $allowedWithoutMfa = [
            'auth.logout',
            'auth.user',
            'auth.password.update',
            'mfa.status',
            'mfa.setup',
            'mfa.enable',
            'mfa.verify',
            'mfa.disable',
            'mfa.backup.regenerate',
        ];

        if (in_array($routeName, $allowedWithoutMfa, true)) {
            return $next($request);
        }

        if (!$user->mfa_enabled) {
            return response()->json([
                'message' => 'MFA enrollment required for this profile.',
                'code' => 'MFA_ENROLL_REQUIRED',
            ], 403);
        }

        $mfaPassed = $request->hasSession() ? (bool) $request->session()->get('mfa_passed', false) : false;

        if (!$mfaPassed) {
            return response()->json([
                'message' => 'MFA verification required.',
                'code' => 'MFA_REQUIRED',
            ], 403);
        }

        return $next($request);
    }
}
