<?php

namespace App\Livewire\Comercio;

use Livewire\Component;
use App\Models\Ubicacion;

class ComercioData extends Component
{
    public Ubicacion $ubicacion;

    public function mount(Ubicacion $ubicacion)
    {
        $this->ubicacion = $ubicacion->load('rubro','documentos');
    }

    public function render()
    {
        return view('livewire.comercio.comercio-data')
            ->layout('admin.layouts.app');
    }
}
