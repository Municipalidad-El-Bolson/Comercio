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
        $this->items = \App\Models\Ubicacion::query()
            ->where('estado_base', '032')                     // ← antes: where('estado','032')
            ->whereDate('fecha_vto', '<', now()->toDateString())
            ->orderByDesc('fecha_vto')                        // tus ubicaciones no usan timestamps
            ->limit(500)
            ->get()
            ->map(function ($u) {
                $nombre = $u->nombre_comercial ?: ($u->razon_social ?: trim(($u->apellido ?? '').' '.($u->nombres ?? '')));
                return [
                    'id'           => $u->id,
                    'nombre'       => $nombre ?: "Ubicación #{$u->id}",
                    'fecha_vto'    => optional($u->fecha_vto)->format('Y-m-d'),
                    'fecha_cambio' => optional($u->fecha_vto)->format('Y-m-d'), // si querés, usá historial
                    'estado'       => $u->estado_base, // 032
                ];
            })
            ->values()
            ->all();
    }

    public function markAllAsRead(): void
    {
        auth()->user()
            ->unreadNotifications()
            ->where('type', \App\Notifications\VencidoNotification::class)
            ->update(['read_at' => now()]);

        $this->mount();
    }


    public function render()
    {
        return view('livewire.vencimientos.vencidos-index');
    }
}