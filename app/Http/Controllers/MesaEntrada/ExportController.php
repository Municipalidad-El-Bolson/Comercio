<?php

namespace App\Http\Controllers\MesaEntrada;

use App\Http\Controllers\Controller;
use App\Models\MesaEntradaRegistro;
use App\Notifications\MesaEntradaNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function inboxExcel(Request $request): StreamedResponse
    {
        return $this->excel($this->inboxRows($request), 'mesa_entrada', 'Mesa de entrada');
    }

    public function inboxPdf(Request $request)
    {
        return $this->pdf($this->inboxRows($request), 'mesa_entrada', 'Mesa de entrada');
    }

    public function historialExcel(Request $request): StreamedResponse
    {
        return $this->excel($this->historialRows($request), 'registro_historico_mesa', 'Registro histórico');
    }

    public function historialPdf(Request $request)
    {
        return $this->pdf($this->historialRows($request), 'registro_historico_mesa', 'Registro histórico de Mesa de entrada');
    }

    private function inboxRows(Request $request): Collection
    {
        $term = mb_strtolower(trim((string) $request->string('search')));

        return $request->user()->notifications()
            ->where('type', MesaEntradaNotification::class)
            ->latest()
            ->get()
            ->filter(function ($notification) use ($request, $term) {
                $data = $notification->data;
                $haystack = mb_strtolower(implode(' ', [
                    data_get($data, 'nro_ingreso'), data_get($data, 'titular'), data_get($data, 'hc'),
                    data_get($data, 'sender_name'), implode(' ', data_get($data, 'docs', [])),
                ]));
                $fecha = (string) data_get($data, 'fecha');

                return ($term === '' || str_contains($haystack, $term))
                    && (!$request->filled('desde') || $fecha >= (string) $request->string('desde'))
                    && (!$request->filled('hasta') || $fecha <= (string) $request->string('hasta'));
            })
            ->map(fn ($notification) => $this->row([
                'fecha' => data_get($notification->data, 'fecha'),
                'nro_ingreso' => data_get($notification->data, 'nro_ingreso'),
                'titular' => data_get($notification->data, 'titular'),
                'hc' => data_get($notification->data, 'hc'),
                'docs' => data_get($notification->data, 'docs', []),
                'usuario' => data_get($notification->data, 'sender_name'),
                'cargado' => $notification->created_at?->format('d/m/Y H:i'),
            ]))->values();
    }

    private function historialRows(Request $request): Collection
    {
        $term = trim((string) $request->string('search'));

        return MesaEntradaRegistro::query()->with('user:id,name')
            ->when($term !== '', fn ($query) => $query->where(function ($subquery) use ($term) {
                $subquery->where('titular_razon', 'like', "%{$term}%")
                    ->orWhere('hc', 'like', "%{$term}%")
                    ->orWhere('nro_ingreso', 'like', "%{$term}%")
                    ->orWhere('sender_name', 'like', "%{$term}%")
                    ->orWhere('documentos', 'like', "%{$term}%");
            }))
            ->when($request->filled('desde'), fn ($query) => $query->whereDate('fecha', '>=', (string) $request->string('desde')))
            ->when($request->filled('hasta'), fn ($query) => $query->whereDate('fecha', '<=', (string) $request->string('hasta')))
            ->orderByDesc('fecha')->orderByDesc('id')->get()
            ->map(fn ($registro) => $this->row([
                'fecha' => $registro->fecha?->format('Y-m-d'),
                'nro_ingreso' => $registro->nro_ingreso,
                'titular' => $registro->titular_razon,
                'hc' => $registro->hc,
                'docs' => $registro->documentos,
                'usuario' => $registro->user?->name ?? $registro->sender_name,
                'cargado' => $registro->created_at?->format('d/m/Y H:i'),
            ]));
    }

    private function row(array $data): array
    {
        return [
            'fecha' => $data['fecha'] ? date('d/m/Y', strtotime($data['fecha'])) : '',
            'nro_ingreso' => (string) ($data['nro_ingreso'] ?? ''),
            'titular' => (string) ($data['titular'] ?? ''),
            'hc' => (string) ($data['hc'] ?? ''),
            'documentacion' => implode(', ', (array) ($data['docs'] ?? [])),
            'usuario' => (string) ($data['usuario'] ?? ''),
            'cargado' => (string) ($data['cargado'] ?? ''),
        ];
    }

    private function excel(Collection $rows, string $prefix, string $sheet): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows, $sheet) {
            echo '<?xml version="1.0" encoding="UTF-8"?><?mso-application progid="Excel.Sheet"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            echo '<Styles><Style ss:ID="Header"><Font ss:Bold="1"/><Interior ss:Color="#D9EAF7" ss:Pattern="Solid"/></Style></Styles>';
            echo '<Worksheet ss:Name="'.htmlspecialchars($sheet, ENT_XML1).'"><Table>';
            foreach ([80, 75, 180, 75, 260, 120, 105] as $width) echo '<Column ss:Width="'.$width.'"/>';
            echo '<Row ss:StyleID="Header">';
            foreach (['Fecha', 'Nº ingreso', 'Titular / Razón social', 'HC', 'Documentación', 'Ingresó', 'Fecha de carga'] as $heading) echo $this->cell($heading);
            echo '</Row>';
            foreach ($rows as $row) {
                echo '<Row>'; foreach ($row as $value) echo $this->cell($value); echo '</Row>';
            }
            echo '</Table></Worksheet></Workbook>';
        }, $prefix.'_'.now()->format('Ymd_His').'.xls', ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    private function pdf(Collection $rows, string $prefix, string $title)
    {
        return Pdf::loadView('mesa-entrada.export-pdf', compact('rows', 'title'))
            ->setPaper('a4', 'landscape')
            ->download($prefix.'_'.now()->format('Ymd_His').'.pdf');
    }

    private function cell(string $value): string
    {
        return '<Cell><Data ss:Type="String">'.htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</Data></Cell>';
    }
}
