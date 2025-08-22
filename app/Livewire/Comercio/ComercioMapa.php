<?php

namespace App\Livewire\Comercio;

use App\Livewire\Admin\AdminComponent;
use App\Models\Ubicacion;
use Livewire\Component;
use App\Models\Rubro;

class ComercioMapa extends AdminComponent
{
    public array $megas = [];
    public array $madres = [];
    public array $subs = [];

    public string $selectedMega = '';
    public string $selectedMadre = '';
    public ?int $selectedSubId = null;

    /** Data mostrada (para primera carga) */
    public array $ubicaciones = [];

    public function mount()
    {
        // Megas distintas
        $this->megas = Rubro::query()
            ->select('mega_rubro')->distinct()->orderBy('mega_rubro')
            ->pluck('mega_rubro')->toArray();

        // Primera carga: todas (o limita si querés)
        $this->ubicaciones = $this->queryUbicaciones()->toArray();
    }

    /** Cuando cambia Mega */
    public function updatedSelectedMega($value)
    {
        $this->selectedMadre = '';
        $this->selectedSubId = null;

        $this->madres = $value
            ? Rubro::where('mega_rubro', $value)
                ->select('rubro_madre')->distinct()->orderBy('rubro_madre')
                ->pluck('rubro_madre')->toArray()
            : [];

        $this->subs = [];

        $this->emitUbicaciones();
    }

    /** Cuando cambia Rubro Madre */
    public function updatedSelectedMadre($value)
    {
        $this->selectedSubId = null;

        $this->subs = ($this->selectedMega && $value)
            ? Rubro::where('mega_rubro', $this->selectedMega)
                ->where('rubro_madre', $value)
                ->orderBy('subrubro')
                ->get(['id','subrubro'])
                ->map(fn($x)=>['id'=>$x->id,'sub'=>$x->subrubro])
                ->toArray()
            : [];

        $this->emitUbicaciones();
    }

    /** Cuando elige Subrubro (guarda rubro_id) */
    public function updatedSelectedSubId($value): void
    {
        // $value llega como string o '' (vacío). Normalizamos:
        $this->selectedSubId = $value !== '' ? (string)$value : null;

        $this->emitUbicaciones();
    }

    private function emitUbicaciones(): void
    {
        $rows = $this->queryUbicaciones();
        $this->ubicaciones = $rows->toArray();

        // Enviar al navegador para redibujar marcadores
        $this->dispatch('ubicacionesUpdated', ubicaciones: $this->ubicaciones);
    }

    private function queryUbicaciones()
    {
        // Convertimos selectedSubId en int sólo si es numérico
        $subId = (is_numeric($this->selectedSubId ?? null))
            ? (int)$this->selectedSubId
            : null;


        return Ubicacion::with('rubro:id,mega_rubro,rubro_madre,subrubro')
            ->when($this->selectedMega !== '', fn ($q) =>
                $q->whereHas('rubro', fn ($r) => $r->where('mega_rubro', $this->selectedMega)))
            ->when($this->selectedMadre !== '', fn ($q) =>
                $q->whereHas('rubro', fn ($r) => $r->where('rubro_madre', $this->selectedMadre)))
            ->when($subId, fn ($q) =>
                $q->where('rubro_id', $subId))
            ->orderBy('razon_social')
            ->get([
                'id', 'razon_social', 'domicilio_comercio',
                'latitud', 'longitud', 'rubro_id'
            ])
            ->map(function ($u) {
                return [
                    'id'                 => $u->id,
                    'razon_social'       => $u->razon_social,
                    'domicilio_comercio' => $u->domicilio_comercio,
                    'latitud'            => $u->latitud,
                    'longitud'           => $u->longitud,
                    'rubro' => [
                        'id'          => $u->rubro?->id,
                        'mega_rubro'  => $u->rubro?->mega_rubro,
                        'rubro_madre' => $u->rubro?->rubro_madre,
                        'subrubro'    => $u->rubro?->subrubro,
                    ],
                ];
            });
    }

    public function render()
    {
       return view('livewire.comercio.comercio-mapa', [
            'megas'  => $this->megas,
            'madres' => $this->madres,
            'subs'   => $this->subs,
            'ubicaciones' => $this->ubicaciones, // para la 1ª carga
        ])
        ->layout('admin.layouts.app');
    }

    public static string $layout = 'admin.layouts.app';
}
