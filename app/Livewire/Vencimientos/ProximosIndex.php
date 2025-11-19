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
        $user = auth()->user();

        // IDs de notificaciones NO leídas (para animación)
        $unread = $user->unreadNotifications
            ->where('type', \App\Notifications\ProxVtoNotification::class)
            ->pluck('data.ubicacion_id')
            ->toArray();

        $this->items = \App\Models\Ubicacion::venceEsteMes()
            ->noVencidos()
            ->soloActivos()
            ->orderBy('fecha_vto')
            ->get()
            ->map(function ($u) use ($unread) {
                $nombre = $u->nombre_comercial ?: ($u->razon_social ?: trim(($u->apellido ?? '').' '.($u->nombres ?? '')));

                return [
                    'id'             => $u->id,
                    'nombre'         => $nombre ?: "Ubicación #{$u->id}",
                    'fecha_vto'      => optional($u->fecha_vto)->format('Y-m-d'),
                    'dias_restantes' => max(0, $u->dias_restantes),
                    'direccion'      => $u->domicilio_comercio,
                    'estado'         => $u->estado,
                    'estado_base'    => $u->estado_base,

                    // 👇 aquí la magia
                    'nuevo'          => in_array($u->id, $unread),
                ];
            })
            ->values()
            ->all();
    }

    public function deleteItem($id): void
    {
        auth()->user()
            ->notifications()
            ->where('type', \App\Notifications\ProxVtoNotification::class)
            ->where('data->ubicacion_id', $id)
            ->delete();

        // Borrar de la lista visual
        $this->items = array_filter($this->items, fn($i) => $i['id'] !== $id);
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