<?php

namespace App\Livewire\MesaEntrada;

use App\Models\MesaEntradaRegistro;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Historial extends Component
{
    use WithPagination;

    public string $search = '';
    public string $fechaDesde = '';
    public string $fechaHasta = '';

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        abort_unless(Gate::allows('mesa-entrada-view'), 403);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'fechaDesde', 'fechaHasta'], true)) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['search', 'fechaDesde', 'fechaHasta']);
        $this->resetPage();
    }

    public function render()
    {
        $term = trim($this->search);
        $historial = MesaEntradaRegistro::query()
            ->with('user:id,name')
            ->when($term !== '', fn ($query) => $query->where(function ($subquery) use ($term) {
                $subquery->where('titular_razon', 'like', "%{$term}%")
                    ->orWhere('hc', 'like', "%{$term}%")
                    ->orWhere('nro_ingreso', 'like', "%{$term}%")
                    ->orWhere('sender_name', 'like', "%{$term}%")
                    ->orWhere('documentos', 'like', "%{$term}%")
                    ->orWhere('observacion', 'like', "%{$term}%");
            }))
            ->when($this->fechaDesde !== '', fn ($query) => $query->whereDate('fecha', '>=', $this->fechaDesde))
            ->when($this->fechaHasta !== '', fn ($query) => $query->whereDate('fecha', '<=', $this->fechaHasta))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20);

        $layout = auth()->user()?->role === 'mesa' ? 'admin.layouts.mesa' : 'admin.layouts.app';
        return view('livewire.mesa-entrada.historial', compact('historial'))->layout($layout);
    }
}
