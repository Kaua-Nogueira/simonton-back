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

        // Admin Bypass
        if ($user->hasRole('admin') || $user->hasRole('Admin')) {
             return $next($request);
        }

        $routeName = Route::currentRouteName();

        // STRICT: Block unnamed routes
        if (!$routeName) {
            \Illuminate\Support\Facades\Log::error('ACL Security Alert: Attempt to access unnamed route', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'url' => $request->url()
            ]);
            return response()->json(['message' => 'Security Error: Route must be named for authorization.'], 403);
        }


        // Exempt basic auth routes that every logged-in user should have
        $exemptRoutes = [
            'auth.logout', 
            'auth.user', 
            'auth.password.update',
            'dashboard.stats', // Usually visible to all dashboard users, or handle with permission "dashboard.view"
        ];

        if (in_array($routeName, $exemptRoutes)) {
            return $next($request);
        }

        if (!$user->hasPermission($routeName)) {
            \Illuminate\Support\Facades\Log::warning('ACL Access Denied', [
                'user_id' => $user->id,
                'user_name' => $user->name,
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
