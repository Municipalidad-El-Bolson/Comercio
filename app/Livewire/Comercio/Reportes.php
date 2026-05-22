<?php

namespace App\Livewire\Comercio;

use App\Models\Ubicacion;
use Livewire\Component;

class Reportes extends Component
{
    public string $reporteActivo = 'proximas-avencer';
    public int $dias = 30;

    public function mostrarReporte(string $tipo): void
    {
        $this->reporteActivo = $tipo;
    }

    public function render()
    {
        $proximasAVencer = Ubicacion::obtenerProximasAVencer($this->dias, ['rubro']);

        $vencidas = Ubicacion::with('rubro')
            ->whereIn('estado', ['vigente', 'irregular'])
            ->where('situacion', 'alta')
            ->where(function ($query) {
                $query->whereNotNull('fecha_vto')
                    ->orWhereNotNull('fecha_alta');
            })
            ->get()
            ->filter(fn (Ubicacion $ubicacion) => $ubicacion->fecha_vencimiento_calculada
                && $ubicacion->fecha_vencimiento_calculada->isBefore(now()->startOfDay()))
            ->sortBy(fn (Ubicacion $ubicacion) => sprintf(
                '%s|%s',
                $ubicacion->fecha_vencimiento_calculada?->format('Y-m-d') ?? '',
                $ubicacion->razon_social ?? ''
            ))
            ->values();

        $porEstado = Ubicacion::query()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->orderBy('estado')
            ->pluck('total', 'estado');

        return view('livewire.comercio.reportes', [
            'proximasAVencer' => $proximasAVencer,
            'vencidas' => $vencidas,
            'porEstado' => $porEstado,
        ])->layout('admin.layouts.app');
    }
}
