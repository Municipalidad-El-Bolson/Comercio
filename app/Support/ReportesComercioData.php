<?php

namespace App\Support;

use App\Models\Rubro;
use App\Models\Ubicacion;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReportesComercioData
{
    public function query(array $filters): Builder
    {
        return Ubicacion::query()
            ->with([
                'rubro:id,rubro_general,mega_rubro,rubro_madre,subrubro',
                'rubros:id,rubro_general,mega_rubro,rubro_madre,subrubro',
                'telefonos:id,ubicacion_id,telefono',
                'estadosHistorial',
            ])
            ->when($filters['rubro_id'] ?? null, function ($query, $rubroId) {
                $query->where(function ($rubros) use ($rubroId) {
                    $rubros->where('rubro_id', $rubroId)
                        ->orWhereHas('rubros', fn ($pivot) => $pivot->where('rubros.id', $rubroId));
                });
            })
            ->when($filters['rubro_general'] ?? null, function ($query, $general) {
                $query->where(function ($rubros) use ($general) {
                    $match = fn ($rubro) => $rubro->where('rubro_general', $general);
                    $rubros->whereHas('rubro', $match)->orWhereHas('rubros', $match);
                });
            })
            ->when($filters['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
            ->when($filters['solo_clausurados'] ?? false, fn ($query) => $query->where('situacion', 'clausurado'))
            ->when($filters['desde'] ?? null, fn ($query, $desde) => $this->whereFechaAsociada($query, '>=', $desde))
            ->when($filters['hasta'] ?? null, fn ($query, $hasta) => $this->whereFechaAsociada($query, '<=', $hasta))
            ->when($filters['proximos_vtos'] ?? null, fn ($query, $dias) => $query->whereBetween('fecha_vto', [
                Carbon::today()->toDateString(),
                Carbon::today()->addDays((int) $dias)->toDateString(),
            ]));
    }

    public function items(array $filters, int $limit = 5000): Collection
    {
        return $this->query($filters)
            ->orderByRaw("CASE WHEN TRIM(COALESCE(nombre_comercial, '')) = '' THEN 1 ELSE 0 END")
            ->orderBy('nombre_comercial')
            ->orderBy('razon_social')
            ->limit($limit)
            ->get()
            ->map(fn (Ubicacion $ubicacion) => $this->formatItem($ubicacion));
    }

    public function formatItem(Ubicacion $ubicacion): array
    {
        $historial = $ubicacion->estadosHistorial->first();
        $estadoBase = $historial?->estado_base ?: $ubicacion->estado_base;
        $estadoLabel = $historial?->estado_label ?: $ubicacion->estado_display;
        $fechaAsociada = Str::startsWith((string) $estadoBase, 'baja')
            ? ($historial?->fecha_baja ?: $ubicacion->fecha_baja)
            : ($historial?->fecha_alta ?: $ubicacion->fecha_alta ?: $historial?->created_at);

        $persona = trim(implode(' ', array_filter([$ubicacion->apellido, $ubicacion->nombres])));
        $titular = collect([$ubicacion->razon_social, $persona])->filter()->unique()->implode(' - ');
        $telefonos = $ubicacion->telefonos->pluck('telefono')->push($ubicacion->telefono)
            ->map(fn ($telefono) => trim((string) $telefono))->filter()->unique()->implode(' / ');

        $rubros = $ubicacion->rubros;
        if ($ubicacion->rubro && ! $rubros->contains('id', $ubicacion->rubro->id)) {
            $rubros = $rubros->prepend($ubicacion->rubro);
        }

        return [
            'nombre_comercial' => $this->value($ubicacion->nombre_comercial),
            'titular' => $titular ?: '-',
            'hc' => $ubicacion->hc ?: $ubicacion->numero_habilitacion ?: '-',
            'rubros' => $rubros->map(fn ($rubro) => collect([
                $rubro->rubro_general, $rubro->mega_rubro, $rubro->rubro_madre, $rubro->subrubro,
            ])->map(fn ($parte) => trim((string) $parte))->filter()->unique()->implode(' > '))
                ->filter()->unique()->implode(' | ') ?: '-',
            'tramite' => $this->tramiteLabel((string) $estadoBase, (string) $estadoLabel),
            'fecha_asociada' => $this->date($fechaAsociada),
            'fecha_asociada_raw' => $fechaAsociada ? Carbon::parse($fechaAsociada)->toDateString() : null,
            'direccion' => $this->shortAddress($ubicacion->domicilio_comercio),
            'telefonos' => $telefonos ?: '-',
            'unidades' => $ubicacion->alojamiento_unidades,
            'plazas' => $ubicacion->alojamiento_plazas,
            'situacion' => Str::headline((string) ($ubicacion->situacion ?: 'sin informar')),
            'suspension_desde' => $this->date($ubicacion->suspension_tasas_desde),
            'suspension_hasta' => $this->date($ubicacion->suspension_tasas_hasta),
            'suspension_desde_raw' => $ubicacion->suspension_tasas_desde?->toDateString(),
            'suspension_hasta_raw' => $ubicacion->suspension_tasas_hasta?->toDateString(),
        ];
    }

    public function filterLabels(array $filters): array
    {
        $rubro = null;
        if ($filters['rubro_id'] ?? null) {
            $rubro = Rubro::find($filters['rubro_id'])?->subrubro;
        }

        return [
            'Rubro' => $rubro ?: ($filters['rubro_general'] ?? null) ?: 'Todos',
            'Estado' => $filters['estado'] ?? null ?: 'Todos',
            'Período' => ($filters['desde'] ?? null) || ($filters['hasta'] ?? null)
                ? (($filters['desde'] ?? null) ?: 'sin límite').' a '.(($filters['hasta'] ?? null) ?: 'sin límite')
                : 'Sin filtro de fecha',
            'Próximos vencimientos' => ($filters['proximos_vtos'] ?? null)
                ? $filters['proximos_vtos'].' días'
                : 'Sin filtro',
            'Sólo clausurados' => ($filters['solo_clausurados'] ?? false) ? 'Sí' : 'No',
        ];
    }

    private function whereFechaAsociada(Builder $query, string $operator, string $date): Builder
    {
        return $query->where(function ($states) use ($operator, $date) {
            $states->where(function ($bajas) use ($operator, $date) {
                $bajas->whereIn('estado', ['baja', 'baja_oficio', 'sin_efecto'])
                    ->whereDate('fecha_baja', $operator, $date);
            })->orWhere(function ($altas) use ($operator, $date) {
                $altas->whereNotIn('estado', ['baja', 'baja_oficio', 'sin_efecto'])
                    ->whereDate('fecha_alta', $operator, $date);
            });
        });
    }

    private function tramiteLabel(string $base, string $label): string
    {
        if (str_contains($label, ' - ')) return trim(explode(' - ', $label, 2)[1]);

        return match ($base) {
            '021' => 'Nueva HC', '032' => '032/01', '040' => '040/25',
            'baja' => 'Baja', 'baja_oficio' => 'Baja de oficio',
            'exp_sin_efecto' => 'Expediente sin efecto', default => $label ?: '-',
        };
    }

    private function date($value): string { return $value ? Carbon::parse($value)->format('d/m/Y') : '-'; }
    private function value(?string $value): string { return trim((string) $value) ?: '-'; }
    private function shortAddress(?string $address): string
    {
        $address = trim((string) $address);
        return $address !== '' ? trim(explode(',', $address, 2)[0]) : '-';
    }
}
