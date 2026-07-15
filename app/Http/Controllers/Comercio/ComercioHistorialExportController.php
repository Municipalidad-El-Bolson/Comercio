<?php

namespace App\Http\Controllers\Comercio;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Ubicacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComercioHistorialExportController extends Controller
{
    public function excel(Ubicacion $ubicacion): StreamedResponse
    {
        $filename = 'historial_comercio_'.$ubicacion->id.'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($ubicacion) {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Fecha', 'Usuario', 'Acción', 'Campo', 'Valor anterior', 'Valor nuevo'], ';');

            foreach ($this->rows($ubicacion) as $row) {
                fputcsv($out, array_values($row), ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(Ubicacion $ubicacion)
    {
        $pdf = Pdf::loadView('comercio.historial-pdf', [
            'ubicacion' => $ubicacion,
            'rows' => $this->rows($ubicacion),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('historial_comercio_'.$ubicacion->id.'_'.now()->format('Ymd_His').'.pdf');
    }

    private function rows(Ubicacion $ubicacion): array
    {
        return $this->audits($ubicacion)->flatMap(function (AuditLog $audit) {
            $diff = Arr::get($audit->meta, 'diff', []);

            if (empty($diff)) {
                return [[
                    'fecha' => $audit->created_at?->format('d/m/Y H:i') ?? '',
                    'usuario' => $audit->user?->name ?? '(sistema)',
                    'accion' => $audit->message,
                    'campo' => '',
                    'anterior' => '',
                    'nuevo' => '',
                ]];
            }

            return collect($diff)
                ->reject(fn ($change, $field) => in_array($field, ['lat', 'lng'], true))
                ->map(function ($change, $field) use ($audit) {
                $label = config('audit.fields.'.$audit->entity_type.'.'.$field)
                    ?? config('audit.fields.*.'.$field)
                    ?? ucfirst(str_replace('_', ' ', $field));

                return [
                    'fecha' => $audit->created_at?->format('d/m/Y H:i') ?? '',
                    'usuario' => $audit->user?->name ?? '(sistema)',
                    'accion' => $audit->message,
                    'campo' => $label,
                    'anterior' => $this->value($change['old'] ?? null),
                    'nuevo' => $this->value($change['new'] ?? null),
                ];
                })->values()->all();
        })->values()->all();
    }

    private function audits(Ubicacion $ubicacion)
    {
        return AuditLog::with('user')
            ->where('entity_type', Ubicacion::class)
            ->where('entity_id', (string) $ubicacion->id)
            ->latest('created_at')
            ->latest('id')
            ->get();
    }

    private function value($value): string
    {
        if ($value === null || $value === '') return '(vacío)';
        if (is_bool($value)) return $value ? 'Sí' : 'No';
        if (is_array($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);

        return (string) $value;
    }
}
