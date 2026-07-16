<?php

namespace App\Http\Controllers\Comercio;

use App\Http\Controllers\Controller;
use App\Models\Ubicacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportesPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $rubroId = $request->integer('rubro_id') ?: null;
        $estado = $request->filled('estado') ? (string) $request->query('estado') : null;
        $rubroGeneral = $request->filled('rubroGeneral') ? (string) $request->query('rubroGeneral') : null;
        $desde = $request->filled('desde') ? (string) $request->query('desde') : null;
        $hasta = $request->filled('hasta') ? (string) $request->query('hasta') : null;
        $proximosVtos = $request->filled('proximos_vtos')
            ? max(1, min((int) $request->integer('proximos_vtos'), 365))
            : null;
        $soloClausurados = $request->boolean('solo_clausurados');

        $items = Ubicacion::query()
            ->with([
                'rubro:id,rubro_general,mega_rubro,rubro_madre,subrubro',
                'rubros:id,rubro_general,mega_rubro,rubro_madre,subrubro',
                'telefonos:id,ubicacion_id,telefono',
                'estadosHistorial',
            ])
            ->when($rubroId, fn ($q) => $q->where('rubro_id', $rubroId))
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->when($soloClausurados, fn ($q) => $q->where('situacion', 'clausurado'))
            ->when($rubroGeneral, fn ($q) => $q->whereHas('rubro', fn ($r) => $r->where('rubro_general', $rubroGeneral)))
            ->when($desde, fn ($q) => $q->where(function ($dates) use ($desde) {
                $dates->whereDate('fecha_alta', '>=', $desde)->orWhereDate('fecha_baja', '>=', $desde);
            }))
            ->when($hasta, fn ($q) => $q->where(function ($dates) use ($hasta) {
                $dates->whereDate('fecha_alta', '<=', $hasta)->orWhereDate('fecha_baja', '<=', $hasta);
            }))
            ->when($proximosVtos, fn ($q) => $q->whereBetween('fecha_vto', [
                Carbon::today()->toDateString(),
                Carbon::today()->addDays($proximosVtos)->toDateString(),
            ]))
            ->orderByRaw("CASE WHEN TRIM(COALESCE(nombre_comercial, '')) = '' THEN 1 ELSE 0 END")
            ->orderBy('nombre_comercial')
            ->orderBy('razon_social')
            ->limit(1000)
            ->get()
            ->map(fn (Ubicacion $ubicacion) => $this->formatItem($ubicacion));

        $filters = [
            'Rubro' => $rubroGeneral ?: ($rubroId ? "ID {$rubroId}" : 'Todos'),
            'Estado' => $estado ?: 'Todos',
            'Período' => ($desde ?: 'sin límite').' a '.($hasta ?: 'sin límite'),
            'Próximos vencimientos' => $proximosVtos ? "{$proximosVtos} días" : 'Todos',
            'Sólo clausurados' => $soloClausurados ? 'Sí' : 'No',
        ];

        return Pdf::loadView('comercio.reportes-pdf', compact('items', 'filters'))
            ->setPaper('a4', 'landscape')
            ->download('reporte_habilitaciones_'.now()->format('Ymd_His').'.pdf');
    }

    private function formatItem(Ubicacion $ubicacion): array
    {
        $historial = $ubicacion->estadosHistorial->first();
        $estadoBase = $historial?->estado_base ?: $ubicacion->estado_base;
        $estadoLabel = $historial?->estado_label ?: $ubicacion->estado_display;
        $fechaAsociada = Str::startsWith((string) $estadoBase, 'baja')
            ? ($historial?->fecha_baja ?: $ubicacion->fecha_baja)
            : ($historial?->fecha_alta ?: $historial?->created_at ?: $ubicacion->fecha_alta);

        $persona = trim(implode(' ', array_filter([$ubicacion->apellido, $ubicacion->nombres])));
        $titular = collect([$ubicacion->razon_social, $persona])->filter()->unique()->implode(' — ');

        $telefonos = $ubicacion->telefonos->pluck('telefono')
            ->push($ubicacion->telefono)
            ->map(fn ($telefono) => trim((string) $telefono))
            ->filter()->unique()->implode(' / ');

        $rubros = $ubicacion->rubros;
        if ($ubicacion->rubro && ! $rubros->contains('id', $ubicacion->rubro->id)) {
            $rubros = $rubros->prepend($ubicacion->rubro);
        }

        return [
            'titular' => $titular ?: '—',
            'hc' => $ubicacion->hc ?: $ubicacion->numero_habilitacion ?: '—',
            'rubros' => $rubros->map(fn ($rubro) => collect([
                $rubro->rubro_general,
                $rubro->mega_rubro,
                $rubro->rubro_madre,
                $rubro->subrubro,
            ])->map(fn ($parte) => trim((string) $parte))->filter()->unique()->implode(' › '))->filter()->unique()->implode(' | ') ?: '—',
            'tramite' => $this->tramiteLabel((string) $estadoBase, (string) $estadoLabel),
            'fecha_asociada' => $this->date($fechaAsociada),
            'direccion' => $this->shortAddress($ubicacion->domicilio_comercio),
            'telefonos' => $telefonos ?: '—',
            'unidades' => $ubicacion->alojamiento_unidades ?? '—',
            'plazas' => $ubicacion->alojamiento_plazas ?? '—',
            'situacion' => Str::headline((string) ($ubicacion->situacion ?: 'sin informar')),
            'suspension_desde' => $this->date($ubicacion->suspension_tasas_desde),
            'suspension_hasta' => $this->date($ubicacion->suspension_tasas_hasta),
        ];
    }

    private function tramiteLabel(string $base, string $label): string
    {
        if (str_contains($label, ' - ')) {
            return trim(explode(' - ', $label, 2)[1]);
        }

        return match ($base) {
            '021' => 'Nueva HC',
            '032' => '032/01',
            '040' => '040/25',
            'baja' => 'Baja',
            'baja_oficio' => 'Baja de oficio',
            'exp_sin_efecto' => 'Expediente sin efecto',
            default => $label ?: '—',
        };
    }

    private function date($value): string
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '—';
    }

    private function shortAddress(?string $address): string
    {
        $address = trim((string) $address);

        return $address !== '' ? trim(explode(',', $address, 2)[0]) : '—';
    }
}
