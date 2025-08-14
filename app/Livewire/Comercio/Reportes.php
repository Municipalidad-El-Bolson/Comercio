<?php

namespace App\Livewire\Comercio;

use Livewire\Component;

class Reportes extends Component
{
    public function render()
    {
        return view('livewire.comercio.reportes')->layout('admin.layouts.app');
    }
}
