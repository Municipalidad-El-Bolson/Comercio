<?php

namespace App\Livewire\MesaEntrada;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Gate;

#[Layout('admin.layouts.app')]
class Inbox extends Component
{
    public array $items = [];

    public function mount(): void
    {
        abort_unless(Gate::allows('mesa-entrada-view'), 403);
        $this->items = auth()->user()
            ->notifications()
            ->where('type', \App\Notifications\MesaEntradaNotification::class)
            ->latest()
            ->take(200)
            ->get()
            ->map(fn ($n) => [
                'id'          => $n->id,
                'read_at'     => $n->read_at,
                'fecha'       => data_get($n->data, 'fecha'),
                'nro_ingreso' => data_get($n->data, 'nro_ingreso'),
                'docs'        => data_get($n->data, 'docs', []),
                'titular'     => data_get($n->data, 'titular'),
                'hc'          => data_get($n->data, 'hc'),
                'sender_name' => data_get($n->data, 'sender_name'),
                'created_at'  => $n->created_at?->format('d/m/Y H:i'),
            ])
            ->toArray();
    }

    public function markAsRead(string $id): void
    {
        $n = auth()->user()->notifications()->findOrFail($id);
        $n->markAsRead();
        $this->mount();
    }

    public function markAllAsRead(): void
    {
        auth()->user()
            ->unreadNotifications()
            ->where('type', \App\Notifications\MesaEntradaNotification::class)
            ->update(['read_at' => now()]);

        $this->mount();
    }


    public function render()
    {
        return view('livewire.mesa-entrada.inbox');
    }
}
