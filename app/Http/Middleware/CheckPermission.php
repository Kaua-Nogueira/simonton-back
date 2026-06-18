<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Route;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Super Admin Bypass
        if ($user->isSuperAdmin()) {
             return $next($request);
        }

        $routeName = Route::currentRouteName();

        // STRICT: Block unnamed routes
        if (!$routeName) {
            \Illuminate\Support\Facades\Log::error('ACL Security Alert: Attempt to access unnamed route', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'url' => $request->url(),
                'method' => $request->method()
            ]);
            return response()->json(['message' => 'Security Error: Route must be named for authorization.'], 403);
        }

        // Exempt basic auth routes that every logged-in user should have
        $exemptRoutes = [
            'auth.logout', 
            'auth.user', 
            'auth.password.update',
            'dashboard.stats', 
            'notifications.index',
            'notifications.read',
            'notifications.read-all',
            'acl.menus.index',
            'portal.me',
            'portal.contributions',
            'societies.financial.movements.attachment',
            'finance.reconciliations.items.attachment',
            'mfa.status',
            'mfa.setup',
            'mfa.enable',
            'mfa.verify',
            'mfa.backup.regenerate',
            'mfa.disable',
            'calendar.assignments.respond',
            'calendar.my-assignments.index',
        ];

        if ($user->must_change_password) {
            $passwordUpdateRoutes = [
                'auth.user',
                'auth.password.update',
                'auth.logout',
            ];

            if (!in_array($routeName, $passwordUpdateRoutes, true)) {
                return response()->json([
                    'message' => 'Password update required before accessing this resource.',
                    'code' => 'PASSWORD_UPDATE_REQUIRED',
                ], 403);
            }
        }

        if (in_array($routeName, $exemptRoutes, true)) {
            return $next($request);
        }

        // Exempt societies.index for any authenticated user with a member_id
        // (the controller policy will still protect it if they shouldn't view it)
        if ($routeName === 'societies.index' && $user->member_id !== null) {
            return $next($request);
        }

        // Dynamic access check for Society leaders
        $route = Route::current();
        if ($route) {
            $society = $route->parameter('society');
            if ($society) {
                if (is_string($society) || is_numeric($society)) {
                    $society = \App\Models\Society::find($society);
                }
                
                if ($society instanceof \App\Models\Society && $user->member_id) {
                    $isLeader = \App\Models\SocietyMandate::where('society_id', $society->id)
                        ->where('year', date('Y'))
                        ->where('status', 'active')
                        ->whereHas('roles', function($q) use ($user) {
                            $q->where('member_id', $user->member_id)
                              ->where('role_type', 'board');
                        })->exists();
                    
                    if ($isLeader) {
                        // Allow leader to access society routes except for deletion of the society itself
                        if (!str_ends_with($routeName, '.destroy') && $routeName !== 'societies.destroy') {
                            return $next($request);
                        }
                    }
                }
            }
        }

        // Dynamic access check for Society leaders on balancete report
        if ($routeName === 'societies.balancete') {
            $societyId = $request->input('society_id');
            if ($societyId && $user->member_id) {
                $isLeader = \App\Models\SocietyMandate::where('society_id', $societyId)
                    ->where('year', date('Y'))
                    ->where('status', 'active')
                    ->whereHas('roles', function($q) use ($user) {
                        $q->where('member_id', $user->member_id)
                          ->where('role_type', 'board');
                    })->exists();
                
                if ($isLeader) {
                    return $next($request);
                }
            }
        }

        if (!$user->hasPermission($routeName)) {
            \Illuminate\Support\Facades\Log::warning('ACL Access Denied', [
                'user_id' => $user->id,
                'role' => $user->roles->pluck('name'),
                'route' => $routeName,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'message' => 'Unauthorized. You do not have the necessary permissions to access this resource.',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        return $next($request);
    }
}
