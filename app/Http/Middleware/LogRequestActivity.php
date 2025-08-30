<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogRequestActivity
{
    public function handle(Request $request, Closure $next): Response
    {

        if (!in_array($request->method(), ['POST','PUT','PATCH','DELETE'], true)) {
            return $next($request);
        }

        // Evitar Livewire payloads
        if (
            $request->routeIs('livewire.*') ||         // nombre de ruta
            $request->is('livewire/*') ||              // path /livewire/...
            $request->headers->has('X-Livewire')       // header propio
        ) {
            return $next($request);
        }

        // Evitar rutas internas/sistemas
        if ($request->routeIs('ignition.*', 'horizon.*', 'sanctum.*')) {
            return $next($request);
        }

        $response = $next($request);

        // Usuario (puede ser null en /login)
        $userId = Auth::id(); // null|int

        $routeName = $request->route()?->getName();
        $action    = $routeName ?: 'request';

        $meta = [
            'query'  => $request->query(),
            'input'  => collect($request->except(['password','password_confirmation','_token']))->take(10),
            'status' => $response->getStatusCode(),
            'ua'     => $request->userAgent(),
        ];

        try {
            AuditLog::create([
                'user_id'     => $userId,           
                'action'      => $action,           
                'entity_type' => null,
                'entity_id'   => null,
                'ip'          => $request->ip(),
                'method'      => $request->method(),
                'path'        => $request->path(),
                'meta'        => $meta,             
            ]);
        } catch (\Throwable $e) {

        }

        return $response;
    }
}
