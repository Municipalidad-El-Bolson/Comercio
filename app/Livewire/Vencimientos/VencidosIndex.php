<?php

namespace App\Livewire\Vencimientos;

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Ubicacion;
use Illuminate\Support\Carbon;

#[Layout('admin.layouts.app')]
class VencidosIndex extends Component
{
    public array $items = [];

    public function mount(): void
    {
        // ya en estado 032, orden por vencimiento más reciente primero
        $this->items = Ubicacion::query()
            ->where('estado', '032')
            ->orderByDesc('updated_at') // o por fecha_vto desc
            ->limit(500)
            ->get()
            ->map(function ($u) {
                $nombre = $u->nombre_comercial ?: ($u->razon_social ?: trim(($u->apellido ?? '').' '.($u->nombres ?? '')));
                return [
                    'id'          => $u->id,
                    'nombre'      => $nombre ?: "Ubicación #{$u->id}",
                    'fecha_vto'   => optional($u->fecha_vto)->format('Y-m-d'),
                    'fecha_cambio'=> optional($u->updated_at)->format('Y-m-d H:i'),
                    'estado'      => $u->estado, // 032
                ];
            })->values()->all();
    }

    public function render()
    {
        return view('livewire.vencimientos.vencidos-index');
    }
}