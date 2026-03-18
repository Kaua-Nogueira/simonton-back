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
        ];

        if (in_array($routeName, $exemptRoutes)) {
            return $next($request);
        }

        if (!$user->hasPermission($routeName)) {
            // Dynamic access for Society leaders/members
            // We allow access at the middleware level because the Controllers/Policies
            // will perform the granular check per society.
            if (str_starts_with($routeName, 'societies.') && $user->member_id !== null) {
                return $next($request);
            }

            \Illuminate\Support\Facades\Log::warning('ACL Access Denied', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'role' => $user->roles->pluck('name'),
                'route' => $routeName,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'message' => "Unauthorized. Missing permission: {$routeName}",
                'permission' => $routeName
            ], 403);
        }

        return $next($request);
    }
}
