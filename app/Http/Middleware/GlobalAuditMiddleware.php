<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class GlobalAuditMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $method = strtoupper($request->method());
        $mutations = ['POST', 'PUT', 'PATCH', 'DELETE'];

        if (in_array($method, $mutations, true) && $response->getStatusCode() < 400) {
            AuditLog::create([
                'user_id' => Auth::id(),
                'auditable_type' => 'SystemRequest',
                'auditable_id' => 0,
                'action' => strtolower($method),
                'new_values' => $this->buildSafeAuditPayload($request),
                'url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'tags' => ['request_log' => true],
            ]);
        }

        return $response;
    }

    private function buildSafeAuditPayload(Request $request): array
    {
        $payload = $request->all();

        $allowed = Arr::only($payload, [
            'id',
            'member_id',
            'society_id',
            'transaction_id',
            'category_id',
            'cost_center_id',
            'status',
            'type',
            'date',
            'amount',
            'year',
            'month',
            'description',
        ]);

        if (isset($allowed['description'])) {
            $allowed['description'] = mb_substr((string) $allowed['description'], 0, 120);
        }

        return [
            'route' => $request->route()?->getName(),
            'method' => strtoupper($request->method()),
            'payload_keys' => array_keys($payload),
            'payload' => $allowed,
        ];
    }
}
