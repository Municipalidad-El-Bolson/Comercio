<?php

namespace App\Livewire\Comercio;

use App\Models\Movimiento;
use Livewire\Component;
use Livewire\WithPagination;

class ActasSeguimiento extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';
    public string $search = '';

    public function updatingSearch(): void { $this->resetPage(); }

    public function render()
    {
        $actas = Movimiento::query()
            ->with('ubicacion')
            ->where('tipo', 'acta')
            ->whereNotNull('fecha_vencimiento')
            ->when(trim($this->search) !== '', function ($query) {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($q) use ($term) {
                    $q->where('titulo', 'like', $term)
                        ->orWhereHas('ubicacion', fn ($u) => $u->where('nombre_comercial', 'like', $term)
                            ->orWhere('razon_social', 'like', $term));
                });
            })
            ->orderBy('fecha_vencimiento')
            ->orderBy('id')
            ->paginate(20);

        return view('livewire.comercio.actas-seguimiento', compact('actas'))
            ->layout('admin.layouts.app');
    }
}
