<?php

// app/Http/Middleware/LogRequestActivity.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AuditLog;

class LogRequestActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Solo usuarios autenticados
        if (!auth()->check()) {
            return $response;
        }

        // Solo métodos que modifican estado
        if (!in_array($request->getMethod(), ['POST','PUT','PATCH','DELETE'], true)) {
            return $response;
        }

        // Excluir paths internos (desde config/audit.php)
        $internal = (array) config('audit.internal_paths', []);
        foreach ($internal as $frag) {
            if ($frag && str_contains($request->path(), $frag)) {
                return $response;
            }
        }

        // Excluir rutas/acciones de sólo vista (si agregás más, ponelas en config)
        $excludedRoutes = (array) config('audit.excluded_routes', []);
        $rname = $request->route()?->getName();
        if ($rname && in_array($rname, $excludedRoutes, true)) {
            return $response;
        }

        // Guardar una actividad genérica SOLO si querés loguear otras acciones mutantes
        // (si preferís registrar SOLO via modelos/traits, podés comentar esto)
        AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => $rname ?: $request->path(),
            'entity_type' => null,
            'entity_id'   => null,
            'ip'          => $request->ip(),
            'method'      => $request->method(),
            'path'        => $request->path(),
            'meta'        => [
                'route'  => $rname,
                // si tenés un mapeo, podés setear meta[action] acá
            ],
        ]);

        return $response;
    }
}
