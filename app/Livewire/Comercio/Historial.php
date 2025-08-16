<?php

namespace App\Livewire\Comercio;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class Historial extends Component
{
    use WithPagination;

    public function render()
    {
        $logs = Activity::with('causer')
            ->latest()
            ->paginate(15);

        return view('livewire.comercio.historial', [
            'logs' => $logs,
        ]);
    }
}
