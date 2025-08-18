<?php

namespace App\Livewire\Comercio;

use Livewire\Component;
use App\Models\Ubicacion;
use Livewire\Attributes\Layout;

class ComercioData extends Component
{
    public Ubicacion $ubicacion;

    public function mount(Ubicacion $ubicacion)
    {
        $this->ubicacion = $ubicacion->load('rubro','documentos');
    }

    public function render()
    {
        $historial = $this->ubicacion
        ->movimientos()
        ->get()
        ->keyBy('etapa'); // o el campo que uses como clave

        return view('livewire.comercio.comercio-data', [
        'ubicacion' => $this->ubicacion,
        'historial' => $historial
        ])->layout('admin.layouts.app');
    }
}
