<?php

namespace App\Livewire\Comercio;

use App\Models\Rubro;
use App\Models\Ubicacion;
use App\Support\ReportesComercioData;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Reportes extends Component
{
    use WithPagination;

    public bool $solo_clausurados = false;
    public ?int $rubro_id = null;
    public ?string $rubroGeneral = null;
    public ?string $estado = null;
    public ?string $desde = null;
    public ?string $hasta = null;
    public ?int $proximosVtos = null;

    public array $rubroOpts = [];
    public array $rubroGenerales = [];

    public function mount(): void
    {
        abort_unless(Gate::allows('access-admin'), 403);

        $this->rubroOpts = Rubro::orderBy('subrubro')->get(['id', 'subrubro'])->toArray();
        $this->rubroGenerales = Rubro::query()->whereNotNull('rubro_general')
            ->where('rubro_general', '!=', '')->distinct()->orderBy('rubro_general')
            ->pluck('rubro_general')->values()->all();
    }

    public function updatedProximosVtos($value): void
    {
        $this->proximosVtos = $value === '' || $value === null
            ? null
            : max(1, min((int) $value, 365));
    }

    private function base()
    {
        return app(ReportesComercioData::class)->query($this->filters());
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['solo_clausurados', 'rubro_id', 'rubroGeneral', 'estado', 'desde', 'hasta', 'proximosVtos']);
        $this->resetPage();
        $this->dispatch('autosave-clear');
    }

    private function filters(): array
    {
        return [
            'rubro_id' => $this->rubro_id,
            'rubro_general' => $this->rubroGeneral,
            'estado' => $this->estado,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'proximos_vtos' => $this->proximosVtos,
            'solo_clausurados' => $this->solo_clausurados,
        ];
    }

    public function getListadoGeneralProperty()
    {
        return $this->base()
            ->with([
                'rubro:id,subrubro',
                'rubros:id,subrubro',
                'estadoModel:codigo,nombre',
            ])
            ->orderBy('razon_social')
            ->paginate(15);
    }

    public function getPorRubroProperty(): array
    {
        $base = $this->base();
        $total = (clone $base)->count();

        $rows = (clone $base)
            ->join('rubros', 'rubros.id', '=', 'ubicaciones.rubro_id')
            ->groupBy('rubros.id', 'rubros.subrubro')
            ->orderBy('cantidad', 'desc')
            ->get([
                'rubros.id',
                'rubros.subrubro',
                DB::raw('COUNT(*) as cantidad'),
            ])
            ->map(function ($r) use ($total) {
                $r->porcentaje = $total ? round(($r->cantidad * 100) / $total, 2) : 0;

                return $r;
            });

        return ['total' => $total, 'items' => $rows];
    }

    public function getPorEstadoProperty(): array
    {
        $base = $this->base();

        $entramite = (clone $base)->where('estado', 'entramite')->count();
        $vigente = (clone $base)->where('estado', 'vigente')->count();
        $irregular = (clone $base)->where('estado', 'irregular')->count();
        $estado040 = (clone $base)->where('estado', '040')->count();
        $baja = (clone $base)->where('estado', 'baja')->count();
        $bajaOficio = (clone $base)->where('estado', 'baja_oficio')->count();
        $sinEfecto = (clone $base)->where('estado', 'sin_efecto')->count();
        $clausurados = (clone $base)->where('situacion', 'clausurado')->count();

        $total = $entramite + $vigente + $irregular + $estado040 + $baja + $bajaOficio + $sinEfecto;
        $pct = fn (int $n) => $total ? round(($n * 100) / $total, 2) : 0;

        return [
            'total' => $total,
            'entramite' => ['n' => $entramite, 'pct' => $pct($entramite)],
            'vigente' => ['n' => $vigente, 'pct' => $pct($vigente)],
            'irregular' => ['n' => $irregular, 'pct' => $pct($irregular)],
            '040' => ['n' => $estado040, 'pct' => $pct($estado040)],
            'baja' => ['n' => $baja, 'pct' => $pct($baja)],
            'baja_oficio' => ['n' => $bajaOficio, 'pct' => $pct($bajaOficio)],
            'sin_efecto' => ['n' => $sinEfecto, 'pct' => $pct($sinEfecto)],
            'clausurados' => ['n' => $clausurados, 'pct' => $pct($clausurados)],
        ];
    }

    public function getHabilitadosPorMesProperty()
    {
        return $this->base()
            ->where('estado', 'vigente')
            ->when($this->desde, fn ($q) => $q->whereDate('fecha_alta', '>=', $this->desde))
            ->when($this->hasta, fn ($q) => $q->whereDate('fecha_alta', '<=', $this->hasta))
            ->selectRaw('YEAR(fecha_alta) as anio, MONTH(fecha_alta) as mes, COUNT(*) as cantidad')
            ->groupBy('anio', 'mes')
            ->orderBy('anio')
            ->orderBy('mes')
            ->get();
    }

    public function getBajasPorMesProperty()
    {
        return $this->base()
            ->where('estado', 'baja')
            ->when($this->desde, fn ($q) => $q->whereDate('fecha_baja', '>=', $this->desde))
            ->when($this->hasta, fn ($q) => $q->whereDate('fecha_baja', '<=', $this->hasta))
            ->selectRaw('YEAR(fecha_baja) as anio, MONTH(fecha_baja) as mes, COUNT(*) as cantidad')
            ->groupBy('anio', 'mes')
            ->orderBy('anio')
            ->orderBy('mes')
            ->get();
    }

    public function getProximosAVencerProperty()
    {
        $desde = Carbon::today();
        $hasta = $this->proximosVtos ? Carbon::today()->addDays($this->proximosVtos) : null;

        return $this->base()
            ->whereIn('estado', ['vigente', '040'])
            ->when(
                $hasta,
                fn ($q) => $q->whereBetween('fecha_vto', [$desde->toDateString(), $hasta->toDateString()]),
                fn ($q) => $q->whereDate('fecha_vto', '>=', $desde->toDateString())
            )
            ->with(['rubro:id,subrubro'])
            ->orderBy('fecha_vto')
            ->take(200)
            ->get([
                'id',
                'razon_social',
                'nombre_comercial',
                'estado',
                'fecha_vto',
                'rubro_id',
            ]);
    }

    public function render()
    {
        return view('livewire.comercio.reportes', [
            'rubroOpts' => $this->rubroOpts,
        ])->layout('admin.layouts.app');
    }
}
