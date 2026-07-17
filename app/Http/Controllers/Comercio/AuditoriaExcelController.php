<?php

namespace App\Http\Controllers\Comercio;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Movimiento;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditoriaExcelController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $query = AuditLog::query()->latest()->with('user');
        if ($request->filled('userName')) $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.$request->query('userName').'%'));
        if ($request->filled('objeto')) {
            $type = ['comercio' => Ubicacion::class, 'acta' => Movimiento::class][$request->query('objeto')] ?? null;
            if ($type) $query->where('entity_type', $type);
        }
        if ($request->filled('accion')) {
            $action = $request->query('accion');
            $query->where(function ($q) use ($action) {
                if ($action === 'crear') $q->where('meta->action', 'created')->orWhere('action', 'like', 'Se creó%');
                if ($action === 'editar') $q->where('meta->action', 'updated')->orWhere('action', 'like', 'Se modificó%')->orWhere('action', 'like', 'Se actualizó%');
                if ($action === 'loguear') $q->where('meta->route', 'login')->orWhere('action', 'login')->orWhere('path', 'like', '%login%');
            });
        }
        if ($request->filled('desde')) $query->where('created_at', '>=', $request->query('desde').' 00:00:00');
        if ($request->filled('hasta')) $query->where('created_at', '<=', $request->query('hasta').' 23:59:59');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Fecha', 'Usuario', 'Acción', 'Detalle'], ';');
            $query->chunk(500, function ($logs) use ($out) {
                foreach ($logs as $log) fputcsv($out, [$log->created_at?->timezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i'), $log->user?->name ?? '-', $log->message ?? $log->action, $log->subtitle ?? ''], ';');
            });
            fclose($out);
        }, 'auditoria_'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
