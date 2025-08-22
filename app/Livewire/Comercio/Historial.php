<?php

namespace App\Livewire\Comercio;

use Livewire\Component;

class Historial extends Component
{
    public function render()
    {
        return view('livewire.comercio.historial')->layout('admin.layouts.app');
    }
}
