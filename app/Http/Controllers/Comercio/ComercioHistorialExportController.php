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
        $filename = 'historial_comercio_'.$ubicacion->id.'_'.now()->format('Ymd_His').'.xls';

        return response()->streamDownload(function () use ($ubicacion) {
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<?mso-application progid="Excel.Sheet"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            echo '<Styles><Style ss:ID="Header"><Font ss:Bold="1"/><Interior ss:Color="#D9EAF7" ss:Pattern="Solid"/></Style></Styles>';
            echo '<Worksheet ss:Name="Historial"><Table>';
            echo '<Column ss:Width="105"/><Column ss:Width="100"/><Column ss:Width="180"/>';
            echo '<Column ss:Width="120"/><Column ss:Width="180"/><Column ss:Width="180"/>';
            echo '<Row ss:StyleID="Header">';
            foreach (['Fecha', 'Usuario', 'Acción', 'Campo', 'Valor anterior', 'Valor nuevo'] as $heading) {
                echo $this->excelCell($heading);
            }
            echo '</Row>';

            foreach ($this->rows($ubicacion) as $row) {
                echo '<Row>';
                foreach ($row as $value) echo $this->excelCell((string) $value);
                echo '</Row>';
            }

            echo '</Table></Worksheet></Workbook>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
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
                        'anterior' => $audit->formatValue($field, $change['old'] ?? null),
                        'nuevo' => $audit->formatValue($field, $change['new'] ?? null),
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

    private function excelCell(string $value): string
    {
        return '<Cell><Data ss:Type="String">'
            .htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            .'</Data></Cell>';
    }
}
