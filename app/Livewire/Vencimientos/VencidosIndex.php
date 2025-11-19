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
        $user = auth()->user();

        // Lista de notificaciones no leídas
        $unread = $user->unreadNotifications
            ->where('type', \App\Notifications\VencidoNotification::class)
            ->pluck('data.ubicacion_id')
            ->toArray();

        $this->items = \App\Models\Ubicacion::query()
            ->where('estado_base', '032')
            ->whereDate('fecha_vto', '<', now()->toDateString())
            ->orderByDesc('fecha_vto')
            ->limit(500)
            ->get()
            ->map(function ($u) use ($unread) {
                $nombre = $u->nombre_comercial ?: ($u->razon_social ?: trim(($u->apellido ?? '').' '.($u->nombres ?? '')));

                return [
                    'id'        => $u->id,
                    'nombre'    => $nombre ?: "Ubicación #{$u->id}",
                    'fecha_vto' => optional($u->fecha_vto)->format('Y-m-d'),
                    'fecha_cambio' => optional($u->fecha_vto)->format('Y-m-d'),
                    'estado'    => $u->estado_base,
                    'nuevo'     => in_array($u->id, $unread),   // ← clave para animación
                ];
            })
            ->values()
            ->all();
    }


    public function deleteItem($id): void
    {
        // Borrar solo la notificación relacionada (si existe)
        auth()->user()
            ->notifications()
            ->where('type', \App\Notifications\VencidoNotification::class)
            ->where('data->ubicacion_id', $id)
            ->delete();

        // Borrar visualmente la fila
        $this->items = array_filter($this->items, fn($i) => $i['id'] !== $id);
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