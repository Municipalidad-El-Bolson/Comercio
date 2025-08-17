<?php

namespace App\Livewire\Comercio;

use Livewire\Component;
use App\Models\Movimiento;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class Timeline extends Component
{
    public int $ubicacionId;

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

    protected bool $tieneFecha = false;

    public function mount(int $ubicacionId)
    {
        $this->ubicacionId = $ubicacionId;
        $this->tieneFecha = Schema::hasColumn('movimientos', 'fecha');

        $ultimo = Movimiento::where('ubicacion_id', $this->ubicacionId)
            ->where('tipo', 'timeline')
            ->when($this->tieneFecha, fn($q) => $q->orderByDesc('fecha'))
            ->orderByDesc('id')
            ->first();

        $this->etapaActual = $ultimo?->etapa ?? array_key_first($this->etapas);
        $this->fecha = Carbon::now()->toDateString();
    }

    public function marcarEtapa()
    {
        $keys = array_keys($this->etapas);
        if (!in_array($this->etapaActual, $keys, true)) {
            $this->addError('etapaActual', 'Etapa inválida.');
            return;
        }

        $titulo = $this->etapas[$this->etapaActual] ?? ucfirst(str_replace('_', ' ', $this->etapaActual));
        $descripcion = $this->obs ?: $titulo;

        $data = [
            'tipo'         => 'timeline',
            'ubicacion_id' => $this->ubicacionId,
            'titulo'       => $titulo,
            'descripcion'  => $descripcion,
            'etapa'        => $this->etapaActual,
            'observacion'  => $this->obs,
        ];
        if ($this->tieneFecha) {
            $data['fecha'] = $this->fecha ?: Carbon::now()->toDateString();
        }

        Movimiento::create($data);

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
        $hist = Movimiento::where('ubicacion_id', $this->ubicacionId)
            ->where('tipo', 'timeline')
            ->whereIn('etapa', array_keys($this->etapas))
            ->when($this->tieneFecha, fn($q) => $q->orderBy('fecha'))
            ->orderBy('id')
            ->get()
            ->groupBy('etapa')
            ->map->last();

        return view('livewire.comercio.timeline', ['historial' => $hist]);
    }
}
