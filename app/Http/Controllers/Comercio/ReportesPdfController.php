<?php

namespace App\Http\Controllers\Comercio;

use App\Http\Controllers\Controller;
use App\Models\Rubro;
use App\Models\Ubicacion;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
            ->when($rubroGeneral, fn ($q) => $q->whereHas('rubro', fn ($r) => $r->where('rubro_general', $rubroGeneral)))
            ->with([
                'rubro:id,subrubro',
                'rubros:id,subrubro',
                'telefonos:id,ubicacion_id,telefono',
            ])
            ->orderByRaw("
                COALESCE(
                    NULLIF(nombre_comercial,''),
                    NULLIF(razon_social,''),
                    CONCAT(TRIM(apellido),' ',TRIM(nombres))
                ) asc
            ")
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

                $telsRel = $r->relationLoaded('telefonos') ? $r->telefonos->pluck('telefono')->filter()->all() : [];
                $telefonos = !empty($telsRel)
                    ? implode(' / ', $telsRel)
                    : (trim((string) ($r->telefono ?? '')) ?: '-');

                $sub = '-';
                if ($r->relationLoaded('rubros') && $r->rubros->count()) {
                    $sub = $r->rubros->first()->subrubro ?? '-';
                } elseif ($r->relationLoaded('rubro')) {
                    $sub = optional($r->rubro)->subrubro ?? '-';
                }

                return [
                    'fantasia' => $fantasia,
                    'titular' => $titular,
                    'telefonos' => $telefonos,
                    'vto' => $r->fecha_vto ? Carbon::parse($r->fecha_vto)->format('Y-m-d') : '-',
                    'direccion' => $r->domicilio_comercio ?: '-',
                    'subrubro' => $sub,
                ];
            });

        $pdf = app('dompdf.wrapper')->loadView('pdf.reporte-habilitaciones', [
            'titulo' => 'Reporte de Habilitaciones Comerciales',
            'desde' => $desde,
            'hasta' => $hasta,
            'filtros' => $filtros,
            'items' => $items,
            'proximosDias' => $proximosVtos,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('reporte_habilitaciones_'.now()->format('Ymd_His').'.pdf');
    }
}
