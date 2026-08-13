<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records every successful state-changing request on the routes it's
 * attached to (admin, monitoring, accounts — per docs/PROJECT_PLAN.md).
 * Runs after the controller so failed/aborted actions (validation errors,
 * 403s, etc.) are never logged: an exception thrown during request
 * handling unwinds past the `return $next($request)` line entirely.
 */
class RecordAuditLog
{
    private const AUDITED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldLog($request, $response)) {
            $this->log($request, $response);
        }

        return $response;
    }

    private function shouldLog(Request $request, Response $response): bool
    {
        return $request->user()
            && in_array($request->method(), self::AUDITED_METHODS, true)
            && $response->getStatusCode() < 400;
    }

    private function log(Request $request, Response $response): void
    {
        $entity = $this->resolveEntity($request);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $request->route()?->getName() ?? $request->method().' '.$request->path(),
            'entity_type' => $entity?->getMorphClass(),
            'entity_id' => $entity?->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => [
                'method' => $request->method(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
            ],
        ]);
    }

    /**
     * The first route-model-bound Eloquent model on the request, if any
     * (e.g. the Course being approved), used to link the log entry to it.
     */
    private function resolveEntity(Request $request): ?Model
    {
        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model) {
                return $parameter;
            }
        }

        return null;
    }
}
