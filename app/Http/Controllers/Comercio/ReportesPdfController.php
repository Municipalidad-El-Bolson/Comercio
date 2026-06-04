<?php

namespace App\Http\Controllers\Comercio;

use App\Http\Controllers\Controller;
use App\Models\Rubro;
use App\Models\Ubicacion;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReportesPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $rubroId = $request->integer('rubro_id') ?: null;
        $estado = $request->filled('estado') ? (string) $request->query('estado') : null;
        $rubroGeneral = $request->filled('rubroGeneral') ? (string) $request->query('rubroGeneral') : null;
        $desde = $request->filled('desde') ? (string) $request->query('desde') : null;
        $hasta = $request->filled('hasta') ? (string) $request->query('hasta') : null;
        $proximosVtos = max(1, min((int) $request->integer('proximos_vtos', 30), 365));
        $soloClausurados = $request->boolean('solo_clausurados');
        $puedeFiltrarRubroGeneral = $rubroGeneral && Schema::hasColumn('rubros', 'rubro_general');

        $estadoVisual = function (?string $e) {
            return match ($e) {
                'entramite' => '021/90',
                'irregular' => '032/01',
                '040' => '040/25',
                'baja' => 'Baja',
                'baja_oficio' => 'Baja de oficio',
                'sin_efecto' => 'Expediente sin efecto',
                null, '' => 'Todos',
                default => $e,
            };
        };

        $subrubroNom = $rubroId
            ? optional(Rubro::find($rubroId))->subrubro
            : null;

        $filtros = [
            'Subrubro' => $subrubroNom ?: 'Todos',
            'Estado' => $estadoVisual($estado),
            'Desde' => $desde ?: '-',
            'Hasta' => $hasta ?: '-',
            'Prox. a vencer (dias)' => (string) $proximosVtos,
            'Solo clausurados' => $soloClausurados ? 'Si' : 'No',
        ];

        $items = Ubicacion::query()
            ->when($rubroId, fn ($q) => $q->where('rubro_id', $rubroId))
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->when($soloClausurados, fn ($q) => $q->where('situacion', 'clausurado'))
            ->when($puedeFiltrarRubroGeneral, fn ($q) => $q->whereHas('rubro', fn ($r) => $r->where('rubro_general', $rubroGeneral)))
            ->with([
                'rubro:id,subrubro',
            ])
            ->orderBy('nombre_comercial')
            ->orderBy('razon_social')
            ->get([
                'id',
                'rubro_id',
                'nombre_comercial',
                'razon_social',
                'apellido',
                'nombres',
                'telefono',
                'fecha_vto',
                'domicilio_comercio',
            ])
            ->map(function ($r) {
                $fantasia = $r->nombre_comercial ?: '-';
                $titular = $r->razon_social ?: trim(($r->apellido ?? '').' '.($r->nombres ?? ''));
                $titular = $titular !== '' ? $titular : '-';

                return [
                    'fantasia' => $fantasia,
                    'titular' => $titular,
                    'telefonos' => trim((string) ($r->telefono ?? '')) ?: '-',
                    'vto' => $r->fecha_vto ? Carbon::parse($r->fecha_vto)->format('Y-m-d') : '-',
                    'direccion' => $r->domicilio_comercio ?: '-',
                    'subrubro' => optional($r->rubro)->subrubro ?? '-',
                ];
            });

        $html = view('pdf.reporte-habilitaciones', [
            'titulo' => 'Reporte de Habilitaciones Comerciales',
            'desde' => $desde,
            'hasta' => $hasta,
            'filtros' => $filtros,
            'items' => $items,
            'proximosDias' => $proximosVtos,
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('a4', 'portrait');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_habilitaciones_'.now()->format('Ymd_His').'.pdf"',
        ]);
    }
}
