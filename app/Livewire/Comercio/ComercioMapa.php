<?php

namespace App\Livewire\Comercio;

use App\Livewire\Admin\AdminComponent;
use App\Models\Ubicacion;
use App\Models\Rubro;
use App\Models\ComercioEstado;
use App\Services\GeoService; // 👈 agregá esto
use Livewire\Component;

class ComercioMapa extends AdminComponent
{
    public array $megas = [];
    public array $madres = [];
    public array $subs = [];

    // 👇 NUEVO
    public array $barrios = [];
    public array $estados = ['entramite' => 'En trámite', 'vigente' => 'Vigente', 'irregular' => 'Irregular', 'baja' => 'Baja'];
    public string $selectedBarrio = '';
    public string $selectedEstado = '';
    public string $fantasiaQuery = '';
    public array $fantasiaSuggestions = [];

    public string $selectedMega = '';
    public string $selectedMadre = '';
    public ?int $selectedSubId = null;

    public array $ubicaciones = [];

    public function mount(GeoService $geo) // 👈 inyectamos GeoService
    {
        // Megas
        $this->megas = Rubro::query()
            ->select('mega_rubro')->distinct()->orderBy('mega_rubro')
            ->pluck('mega_rubro')->toArray();

        // Barrios desde GeoJSON
        $this->barrios = $geo->barriosList();

        // Primera carga
        $this->ubicaciones = $this->queryUbicaciones()->toArray();
    }

    public function updatedSelectedMega($v) { $this->selectedMadre = ''; $this->selectedSubId = null; 
        $this->madres = $v
            ? Rubro::where('mega_rubro', $v)->select('rubro_madre')->distinct()->orderBy('rubro_madre')->pluck('rubro_madre')->toArray()
            : [];
        $this->subs = []; $this->emitUbicaciones();
    }

    public function updatedSelectedMadre($v) {
        $this->selectedSubId = null;
        $this->subs = ($this->selectedMega && $v)
            ? Rubro::where('mega_rubro',$this->selectedMega)->where('rubro_madre',$v)
                ->orderBy('subrubro')->get(['id','subrubro'])
                ->map(fn($x)=>['id'=>$x->id,'sub'=>$x->subrubro])->toArray()
            : [];
        $this->emitUbicaciones();
    }

    public function updatedSelectedSubId($v): void {
        $this->selectedSubId = is_numeric($v) ? (int)$v : null;
        $this->emitUbicaciones();
    }

    // 👇 NUEVO: cuando cambian los filtros extra
    public function updatedSelectedBarrio() { $this->emitUbicaciones(); }
    public function updatedSelectedEstado() { $this->emitUbicaciones(); }

    // 👇 NUEVO: autocompletar “nombre de fantasía”
    public function updatedFantasiaQuery($value) {
        $value = trim((string)$value);
        if ($value === '' || mb_strlen($value) < 2) { // esperá 2+ letras
            $this->fantasiaSuggestions = [];
            $this->emitUbicaciones();
            return;
        }

        // Sugerencias (solo nombres comerciales)
        $t = '%'.$value.'%';
        $this->fantasiaSuggestions = Ubicacion::query()
            ->whereNotNull('nombre_comercial')->where('nombre_comercial','<>','')
            ->where('nombre_comercial','like',$t)
            ->orderBy('nombre_comercial')
            ->limit(10)
            ->pluck('nombre_comercial')
            ->toArray();

        // Refrescar resultados del mapa con el filtro de texto aplicado
        $this->emitUbicaciones();
    }

    private function emitUbicaciones(): void
    {
        $rows = $this->queryUbicaciones();
        $this->ubicaciones = $rows->toArray();
        $this->dispatch('ubicacionesUpdated', ubicaciones: $this->ubicaciones);
    }

    private function queryUbicaciones()
    {
        $subId = is_numeric($this->selectedSubId ?? null) ? (int)$this->selectedSubId : null;
        $fantasia = trim($this->fantasiaQuery ?? '');

        return Ubicacion::with('rubro:id,mega_rubro,rubro_madre,subrubro')
            // filtros de rubro
            ->when($this->selectedMega !== '', fn ($q) =>
                $q->whereHas('rubro', fn ($r) => $r->where('mega_rubro', $this->selectedMega)))
            ->when($this->selectedMadre !== '', fn ($q) =>
                $q->whereHas('rubro', fn ($r) => $r->where('rubro_madre', $this->selectedMadre)))
            ->when($subId, fn ($q) => $q->where('rubro_id', $subId))

            // 👇 filtros nuevos
            ->when($this->selectedBarrio !== '', fn($q)=> $q->where('barrio', $this->selectedBarrio))
            ->when($this->selectedEstado !== '', fn($q)=> $q->where('estado', $this->selectedEstado))
            ->when($fantasia !== '', function($q) use ($fantasia) {
                $t = '%'.$fantasia.'%';
                $q->where(function($w) use ($t) {
                    $w->where('nombre_comercial','like',$t);
                    // si querés que también matchee razón social, descomentalo:
                    // ->orWhere('razon_social','like',$t);
                });
            })

            ->orderByRaw("COALESCE(NULLIF(nombre_comercial,''), razon_social) asc")
            ->get([
                'id','razon_social','nombre_comercial','domicilio_comercio',
                'lat','lng','rubro_id','barrio','estado'
            ])
            ->map(function ($u) {
                return [
                    'id'                 => $u->id,
                    'razon_social'       => $u->razon_social,
                    'nombre_comercial'   => $u->nombre_comercial,
                    'domicilio_comercio' => $u->domicilio_comercio,
                    'lat'                => $u->lat,
                    'lng'                => $u->lng,
                    // compatibilidad si tu JS todavía lee estos nombres
                    'latitud'            => $u->lat,
                    'longitud'           => $u->lng,
                    'barrio'             => $u->barrio,
                    'estado'             => $u->estado,
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

            // 👇 pasar filtros nuevos
            'barrios' => $this->barrios,
            'estados' => $this->estados,

            'ubicaciones' => $this->ubicaciones,
        ])->layout('admin.layouts.app');
    }

    public static string $layout = 'admin.layouts.app';
}
