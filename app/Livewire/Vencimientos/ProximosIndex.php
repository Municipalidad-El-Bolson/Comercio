<?php

namespace App\Livewire\Vencimientos;

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Ubicacion;

#[Layout('admin.layouts.app')]
class ProximosIndex extends Component
{
    public array $items = [];

    public function mount(): void
    {

        $this->items = \App\Models\Ubicacion::venceEsteMes()
            ->noVencidos()                  // ahora excluye “hoy”
            ->soloActivos()                 // opcional: evita 032/bajas
            ->orderBy('fecha_vto')
            ->get()
            ->map(function (\App\Models\Ubicacion $u) {
                $nombre = $u->nombre_comercial ?: ($u->razon_social ?: trim(($u->apellido ?? '').' '.($u->nombres ?? '')));
                return [
                    'id'             => $u->id,
                    'nombre'         => $nombre ?: "Ubicación #{$u->id}",
                    'fecha_vto'      => optional($u->fecha_vto)->format('Y-m-d'),
                    'dias_restantes' => max(0, $u->dias_restantes),
                    'direccion'      => $u->domicilio_comercio,
                    'estado'         => $u->estado,          // canónico (entramite/irregular/040)
                    'estado_base'    => $u->estado_base,     // 021/032/040
                ];
            })
            ->values()
            ->all();

    }

    public function markAllAsRead(): void
    {
        auth()->user()
            ->unreadNotifications()
            ->where('type', \App\Notifications\ProxVtoNotification::class)
            ->update(['read_at' => now()]);

        $this->mount();
    }

    public function render()
    {
        return view('livewire.vencimientos.proximos-index');
    }
}