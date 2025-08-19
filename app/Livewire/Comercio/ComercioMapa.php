<?php

namespace App\Livewire\Comercio;

use App\Livewire\Admin\AdminComponent;
use App\Models\Ubicacion;
use Livewire\Component;

class ComercioMapa extends AdminComponent
{
    public function render()
    {
        $ubicaciones = Ubicacion::with('rubro')
            ->paginate(10);

        return view('livewire.comercio.comercio-mapa', [
            'ubicaciones' => $ubicaciones
        ])->layout('admin.layouts.app');
    }
}
