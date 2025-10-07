<?php

namespace App\Livewire\Vencimientos;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Carbon;
use App\Models\Ubicacion;

#[Layout('admin.layouts.app')]
class ProximosIndex extends Component
{
    public array $items = [];

    public function mount(): void
    {
        $hoy = Carbon::today();
        $ini = $hoy->copy()->startOfMonth()->toDateString();
        $fin = $hoy->copy()->endOfMonth()->toDateString();

        $this->items = Ubicacion::query()
            ->whereNotNull('fecha_vto')
            ->whereBetween('fecha_vto', [$ini, $fin])
            ->orderBy('fecha_vto') // más urgentes arriba
            ->get()
            ->map(function ($u) use ($hoy) {
                $nombre = $u->nombre_comercial ?: ($u->razon_social ?: trim(($u->apellido ?? '').' '.($u->nombres ?? '')));
                $dias   = Carbon::parse($u->fecha_vto)->diffInDays($hoy, false); // negativo si futuro
                return [
                    'id'            => $u->id,
                    'nombre'        => $nombre ?: "Ubicación #{$u->id}",
                    'fecha_vto'     => optional($u->fecha_vto)->format('Y-m-d'),
                    'dias_restantes'=> max(0, Carbon::parse($u->fecha_vto)->diffInDays($hoy)),
                    'direccion'     => $u->domicilio_comercio,
                    'estado'        => $u->estado,
                ];
            })
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.vencimientos.proximos-index');
    }
}