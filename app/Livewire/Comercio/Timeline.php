<?php

namespace App\Livewire\Comercio;

use App\Models\Ubicacion;
use App\Models\Movimiento; // ya lo tenías en tu proyecto
use Illuminate\Support\Carbon;
use Livewire\Component;

class Timeline extends Component
{
    public int $ubicacionId;

    /** Orden y rótulos de las etapas del circuito */
    public array $etapas = [
        'comercio_inicio'   => 'Comercio',
        'legales'           => 'Legales',
        'comercio_revision' => 'Comercio (revisión)',
        'fiscalia'          => 'Fiscalía',
        'lugar_x'           => 'Lugar X',
        'lugar_y'           => 'Lugar Y',
        'comercio_final'    => 'Comercio (final)',
    ];

    public ?string $etapaActual = null;   // key de la etapa actual
    public ?string $fecha = null;         // fecha del cambio (opcional)
    public ?string $obs = null;           // observación (opcional)

    public function mount(int $ubicacionId)
    {
        $this->ubicacionId = $ubicacionId;

        // tomar último movimiento como "etapa actual"
        $ultimo = Movimiento::where('ubicacion_id', $this->ubicacionId)
            ->orderByDesc('fecha')->orderByDesc('id')->first();

        $this->etapaActual = $ultimo?->etapa ?? array_key_first($this->etapas);
        $this->fecha = Carbon::now()->format('Y-m-d');
    }

    public function marcarEtapa()
    {
        $keys = array_keys($this->etapas);
        if (!in_array($this->etapaActual, $keys, true)) {
            $this->addError('etapaActual', 'Etapa inválida.');
            return;
        }

        Movimiento::create([
            'ubicacion_id' => $this->ubicacionId,
            'etapa'        => $this->etapaActual,
            'fecha'        => $this->fecha ?: now()->format('Y-m-d'),
            'observacion'  => $this->obs,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Etapa actualizada');
    }

    public function avanzar()
    {
        $keys = array_values(array_keys($this->etapas));
        $idx  = array_search($this->etapaActual, $keys, true);
        $next = $idx === false ? 0 : min($idx + 1, count($keys) - 1);
        $this->etapaActual = $keys[$next];
        $this->marcarEtapa();
    }

    public function render()
    {
        // historial por etapa (última fecha vista)
        $hist = Movimiento::where('ubicacion_id', $this->ubicacionId)
            ->whereIn('etapa', array_keys($this->etapas))
            ->orderBy('fecha')->get()
            ->groupBy('etapa')
            ->map->last();

        return view('livewire.comercio.timeline', [
            'historial' => $hist, // etapa => Movimiento
        ]);
    }
}
