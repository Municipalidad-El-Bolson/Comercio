<?php

namespace App\Livewire\MesaEntrada;

use App\Models\MesaEntradaRegistro;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Gate;

#[Layout('admin.layouts.app')]
class Inbox extends Component
{
    use WithPagination;

    public array $items = [];
    public string $search = '';
    public string $fechaDesde = '';
    public string $fechaHasta = '';

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        abort_unless(Gate::allows('mesa-entrada-view'), 403);

        $user = auth()->user();

        // IDs de no leídas (para animar)
        $unread = $user->unreadNotifications
            ->where('type', \App\Notifications\MesaEntradaNotification::class)
            ->pluck('id')
            ->toArray();

        $this->items = $user->notifications()
            ->where('type', \App\Notifications\MesaEntradaNotification::class)
            ->latest()
            ->take(200)
            ->get()
            ->map(fn ($n) => [
                'id'          => $n->id,
                'read_at'     => $n->read_at,
                'nuevo'       => in_array($n->id, $unread), // ← ANIMACIÓN
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

    public function updatingSearch(): void
    {
        $this->resetPage('historialPage');
    }

    public function updatedFechaDesde(): void
    {
        $this->resetPage('historialPage');
    }

    public function updatedFechaHasta(): void
    {
        $this->resetPage('historialPage');
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['search', 'fechaDesde', 'fechaHasta']);
        $this->resetPage('historialPage');
    }


    public function deleteItem(string $id): void
    {
        auth()->user()
            ->notifications()
            ->where('id', $id)
            ->delete();

        // Remover visualmente de la lista
        $this->items = array_filter($this->items, fn($i) => $i['id'] !== $id);
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
        $term = trim($this->search);

        $historial = MesaEntradaRegistro::query()
            ->with('user:id,name')
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($subquery) use ($term) {
                    $subquery->where('titular_razon', 'like', "%{$term}%")
                        ->orWhere('hc', 'like', "%{$term}%")
                        ->orWhere('nro_ingreso', 'like', "%{$term}%")
                        ->orWhere('sender_name', 'like', "%{$term}%")
                        ->orWhere('documentos', 'like', "%{$term}%");
                });
            })
            ->when($this->fechaDesde !== '', fn ($query) => $query->whereDate('fecha', '>=', $this->fechaDesde))
            ->when($this->fechaHasta !== '', fn ($query) => $query->whereDate('fecha', '<=', $this->fechaHasta))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'historialPage');

        return view('livewire.mesa-entrada.inbox', compact('historial'));
    }
}
