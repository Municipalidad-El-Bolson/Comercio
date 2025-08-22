<?php

namespace App\Livewire\Comercio;

use Livewire\Component;
use App\Models\Movimiento;
use App\Models\Ubicacion;
use Illuminate\Support\Carbon;
use App\Models\ComercioEstado;

class Timeline extends Component
{
    public int $ubicacionId;

    /** Recibe created-at desde Blade (puede venir Carbon o string o null) */
    public $createdAt = null;

    public array $etapas = [
        'comercio_inicio'   => 'Comercio',
        'legales'           => 'Legales',
        'comercio_revision' => 'Comercio (revisión)',
        'fiscalia'          => 'Fiscalía',
        'lugar_x'           => 'Lugar X',
        'lugar_y'           => 'Lugar Y',
        'comercio_final'    => 'Comercio (final)',
    ];

    public ?string $etapaActual = null;
    public ?string $fecha = null;
    public ?string $obs = null;
    public bool $colapsado = false;

    public function mount(int $ubicacionId, $createdAt = null)
    {
        $this->ubicacionId = $ubicacionId;

        // Normalizo createdAt -> string YYYY-MM-DD
        if ($createdAt instanceof Carbon) {
            $this->createdAt = $createdAt->toDateString();
        } elseif (is_string($createdAt) && $createdAt !== '') {
            $this->createdAt = Carbon::parse($createdAt)->toDateString();
        } else {
            // fallback: lo leo de la ubicación si no vino
            $this->createdAt = optional(Ubicacion::find($ubicacionId))->created_at?->toDateString();
        }

        // Etapa actual = último movimiento (o primera etapa)
        $ultimo = Movimiento::where('ubicacion_id', $this->ubicacionId)
            ->orderByDesc('fecha')->orderByDesc('id')->first();

        $this->etapaActual = $ultimo?->etapa ?? array_key_first($this->etapas);
        $this->fecha = now()->toDateString();
    }

    public function marcarEtapa()
    {
        $keys = array_keys($this->etapas);
        if (!in_array($this->etapaActual, $keys, true)) {
            $this->addError('etapaActual', 'Etapa inválida.');
            return;
        }

        $titulo = $this->etapas[$this->etapaActual];
        Movimiento::create([
            'ubicacion_id' => $this->ubicacionId,
            'titulo'       => $titulo,
            'etapa'        => $this->etapaActual,
            'observacion'  => $this->obs,
            'fecha'        => $this->fecha ?: now()->toDateString(),
        ]);

        $this->dispatch('toast', type: 'success', message: 'Etapa actualizada');
    }

    public function avanzar()
    {
       // Primero guarda la etapa actual…
        $this->marcarEtapa();

        // …y luego mueve el selector al siguiente paso
        $keys = array_keys($this->etapas);
        $idx  = array_search($this->etapaActual, $keys, true);
        $next = $idx === false ? 0 : min($idx + 1, count($keys) - 1);
        $this->etapaActual = $keys[$next];
    }

    public function render()
    {
        $historial = Movimiento::where('ubicacion_id', $this->ubicacionId)
            ->whereIn('etapa', array_keys($this->etapas))
            ->orderBy('fecha')->get()
            ->groupBy('etapa')
            ->map->last();

        return view('livewire.comercio.timeline', [
            'historial' => $historial,
            'createdAt' => $this->createdAt,
        ]);
    }
}
