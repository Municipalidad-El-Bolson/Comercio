<?php

namespace App\Livewire;

use Livewire\Component;

class Counter extends Component
{
    public $count = 1;
    public $variable = Null;

    public function increment()
    {
        $this->count++;
    }

    public function decrement()
    {
        $this->count--;
    }

    public function render()
    {
        // return view('livewire.counter')->layout('admin.layouts.app');
        return view('livewire.comercio.counter');
    }
}
