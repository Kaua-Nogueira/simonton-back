<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class GlobalAuditMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log mutation methods and successful/redirect responses
        $method = strtoupper($request->method());
        $mutations = ['POST', 'PUT', 'PATCH', 'DELETE'];

        if (in_array($method, $mutations) && $response->getStatusCode() < 400) {
            // This captures the request context even if no model events were fired
            // We use 'system' as the auditable_type for general request logs
            // with action being the HTTP method.
            AuditLog::create([
                'user_id' => Auth::id(),
                'auditable_type' => 'SystemRequest',
                'auditable_id' => 0,
                'action' => strtolower($method),
                'new_values' => $this->filterPayload($request->all()),
                'url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'tags' => ['request_log' => true]
            ]);
        }

        return $response;
    }

    /**
     * Filter payload to remove sensitive data.
     */
    private function filterPayload(array $payload): array
    {
        $sensitive = ['password', 'password_confirmation', 'token', 'access_token', 'remember_token'];
        
        foreach ($sensitive as $field) {
            if (isset($payload[$field])) {
                $payload[$field] = '[FILTERED]';
            }
        }

        return $payload;
    }
}
