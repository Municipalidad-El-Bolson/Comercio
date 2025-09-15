<?php

namespace App\Livewire\Comercio;

use App\Livewire\Admin\AdminComponent;
use App\Models\Ubicacion;
use App\Models\Rubro;
use App\Services\GeoService;
use Illuminate\Support\Facades\Gate;

class ComercioMapa extends AdminComponent
{
    /** NO pública: Livewire no debe serializar servicios */
    protected GeoService $geo;

    // ====== Filtros y datos ======
    public array $megas = [];
    public array $madres = [];
    public array $subs = [];

    public array $barrios = [];
    public array $estados = [
        'entramite' => 'En trámite',
        'vigente'   => 'Vigente',
        'irregular' => 'Irregular',
        'baja'      => 'Baja'
    ];

    public string $selectedBarrio = '';
    public string $selectedEstado = '';
    public string $fantasiaQuery = '';
    public array  $fantasiaSuggestions = [];

    // Rubro como en el form + nomenclatura
    public array $rubroOpts = [];
    public array $nomenOpts = [];
    public ?int  $selectedRubroId = null;
    public string $selectedNomen = '';

    public array $ubicaciones = [];

    public function mount(GeoService $geo)
    {
        abort_unless(Gate::allows('view-maps'), 403);

        // inicializar servicio (ya no es pública)
        $this->geo = $geo;

        // opciones de filtros
        $this->megas = Rubro::query()
            ->select('mega_rubro')->distinct()->orderBy('mega_rubro')
            ->pluck('mega_rubro')->toArray();

        $this->rubroOpts = Rubro::orderBy('subrubro')
            ->limit(50)->get(['id','subrubro'])->toArray();

        $this->barrios   = $this->geo->barriosList();
        $this->nomenOpts = $this->buildNomenOpts();

        $this->ubicaciones = $this->queryUbicaciones()->toArray();
    }

    public function updatedSelectedBarrio()  { $this->emitUbicaciones(); }
    public function updatedSelectedEstado()  { $this->emitUbicaciones(); }
    public function updatedSelectedRubroId() { $this->emitUbicaciones(); }
    public function updatedSelectedNomen()   { $this->emitUbicaciones(); }

    public function updatedFantasiaQuery($value)
    {
        $value = trim((string)$value);
        if ($value === '' || mb_strlen($value) < 2) {
            $this->fantasiaSuggestions = [];
            $this->emitUbicaciones();
            return;
        }

        $t = '%'.$value.'%';
        $this->fantasiaSuggestions = Ubicacion::query()
            ->whereNotNull('nombre_comercial')->where('nombre_comercial','<>','')
            ->where('nombre_comercial','like',$t)
            ->orderBy('nombre_comercial')
            ->limit(10)
            ->pluck('nombre_comercial')
            ->toArray();

        $this->emitUbicaciones();
    }

    /** Click en el mapa → abrir form de alta con lat/lng/nomen prellenados */
    public function crearDesdeMapa(float $lat, float $lng): void
    {
        $cpu   = $this->geo->matchCpu($lat, $lng);
        $nomen = $cpu['cpu_cod'] ?? null;

        $base = url('/admin/ubicaciones'); // ajustá si tu ruta es otra
        $qs = http_build_query(array_filter([
            'open'  => 'create',
            'lat'   => $lat,
            'lng'   => $lng,
            'nomen' => $nomen,
        ], fn($v) => !is_null($v) && $v !== ''));

        $this->dispatch('redirigir-a', url: $base.'?'.$qs);
    }

    private function emitUbicaciones(): void
    {
        $rows = $this->queryUbicaciones();
        $this->ubicaciones = $rows->toArray();
        $this->dispatch('ubicacionesUpdated',
            ubicaciones: $this->ubicaciones,
            selectedNomen: $this->selectedNomen
        );
    }

    private function queryUbicaciones()
    {
        $fantasia = trim($this->fantasiaQuery ?? '');

        return Ubicacion::with('rubro:id,mega_rubro,rubro_madre,subrubro')
            ->when($this->selectedRubroId, fn($q) => $q->where('rubro_id', $this->selectedRubroId))
            ->when($this->selectedBarrio !== '', fn($q)=> $q->where('barrio', $this->selectedBarrio))
            ->when($this->selectedEstado !== '', fn($q)=> $q->where('estado', $this->selectedEstado))
            ->when($this->selectedNomen !== '', function ($q) {
                $nom = $this->selectedNomen;
                $q->where(function($w) use ($nom) {
                    $w->where('cpu_cod', $nom)->orWhere('nomenclatura', $nom);
                });
            })
            ->when($fantasia !== '', function($q) use ($fantasia) {
                $t = '%'.$fantasia.'%';
                $q->where('nombre_comercial','like',$t);
            })
            ->orderByRaw("COALESCE(NULLIF(nombre_comercial,''), razon_social) asc")
            ->get([
                'id','razon_social','nombre_comercial','domicilio_comercio',
                'lat','lng','rubro_id','barrio','estado','cpu_cod','nomenclatura'
            ])
            ->map(function ($u) {
                return [
                    'id'                 => $u->id,
                    'razon_social'       => $u->razon_social,
                    'nombre_comercial'   => $u->nombre_comercial,
                    'domicilio_comercio' => $u->domicilio_comercio,
                    'lat'                => $u->lat,
                    'lng'                => $u->lng,
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

    private function buildNomenOpts(): array
    {
        try {
            $path = public_path('geo/CATASTRO_GEO.json');
            if (!is_file($path)) return [];
            $data = json_decode(file_get_contents($path), true);
            $features = $data['features'] ?? [];
            $vals = [];

            foreach ($features as $f) {
                $p = $f['properties'] ?? [];
                foreach (['NOMEN','NOMENC','NOMENCLATURA','RefName','refname','nomenclatura'] as $k) {
                    if (!empty($p[$k])) { $vals[] = trim((string)$p[$k]); break; }
                }
            }
            $vals = array_values(array_unique(array_filter($vals)));
            natcasesort($vals);
            return array_values($vals);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function render()
    {
        return view('livewire.comercio.comercio-mapa', [
            'megas'        => $this->megas,
            'madres'       => $this->madres,
            'subs'         => $this->subs,
            'barrios'      => $this->barrios,
            'estados'      => $this->estados,
            'ubicaciones'  => $this->ubicaciones,
            'rubroOpts'    => $this->rubroOpts,
            'nomenOpts'    => $this->nomenOpts,
        ])->layout('admin.layouts.app');
    }

    public static string $layout = 'admin.layouts.app';
}
