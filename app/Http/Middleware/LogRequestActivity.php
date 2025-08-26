<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogRequestActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // solo mutaciones (evitamos ruido de GET)
        if (!in_array($request->method(), ['POST','PUT','PATCH','DELETE'])) {
            return $response;
        }

        if (
            $request->routeIs('livewire.*') ||     // nombre de ruta livewire.update
            $request->is('livewire/*') ||          // path /livewire/update
            $request->headers->has('X-Livewire')   // header que manda Livewire
        ) {
            return $response;
        }

        if ($request->routeIs('ignition.*', 'horizon.*', 'sanctum.*')) {
           return $response;
        }

        AuditLog::create([
            'user_id'     => $user?->id,
            'action'      => $route ?: 'request',
            'entity_type' => null,
            'entity_id'   => null,
            'ip'          => $request->ip(),
            'method'      => $request->method(),
            'path'        => $request->path(),
            'meta'        => [
                'query' => $request->query(),
                'input' => collect($request->except(['password','password_confirmation','_token']))->take(10),
                'status'=> $response->getStatusCode(),
            ],
        ]);

        return $response;
    }
}
