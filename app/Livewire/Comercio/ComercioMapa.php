<?php

namespace App\Livewire\Comercio;

use App\Livewire\Admin\AdminComponent;
use App\Models\Ubicacion;
use Livewire\Component;

class ComercioMapa extends AdminComponent
{
    public function render()
    {

        $ubicaciones = Ubicacion::all();

        return view('livewire.comercio.comercio-mapa', compact('ubicaciones'))->layout('admin.layouts.app');
    }
}
