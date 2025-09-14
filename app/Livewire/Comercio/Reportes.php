<?php

namespace App\Livewire\Comercio;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\Ubicacion;
use App\Models\Rubro;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Barryvdh\DomPDF\Facade\Pdf;

class Reportes extends Component
{
    use WithPagination;

    // Filtros
    public ?int $rubro_id = null;        // Subrubro (id)
    public ?string $estado = null;
    public ?string $desde = null;
    public ?string $hasta = null;
    public int $proximos_vtos = 30;

    // Opciones para el TomSelect
    public array $rubroOpts = [];        // [ ['id'=>..., 'subrubro'=>...], ...]

    public function mount()
    {
        abort_unless(Gate::allows('access-admin'), 403);

        // Opciones de rubros (todo el catálogo, ordenado)
        $this->rubroOpts = Rubro::orderBy('subrubro')->get(['id','subrubro'])->toArray();

        // Rango por defecto
        $this->desde = Carbon::now()->startOfYear()->toDateString();
        $this->hasta = Carbon::now()->toDateString();
    }

    /** Query base con filtros aplicados */
    private function base()
    {
        return Ubicacion::query()
            ->when($this->rubro_id, fn($q) => $q->where('rubro_id', $this->rubro_id))
            ->when($this->estado,   fn($q) => $q->where('estado', $this->estado));
    }

    // ---------- EXPORTAR PDF ----------
    public function exportarPdf()
    {
        $subrubroNom = $this->rubro_id
            ? optional(Rubro::find($this->rubro_id))->subrubro
            : null;

        $filtros = [
            'Subrubro'               => $subrubroNom ?: 'Todos',
            'Estado'                 => $this->estado ?: 'Todos',
            'Desde'                  => $this->desde ?: '—',
            'Hasta'                  => $this->hasta ?: '—',
            'Próx. a vencer (días)'  => (string)($this->proximos_vtos ?? 30),
        ];

        $items = $this->base()
            ->with([
                'rubro:id,subrubro',
                'rubros:id,subrubro',
                'telefonos:id,ubicacion_id,telefono',
            ])
            ->orderByRaw("
                COALESCE(
                    NULLIF(nombre_comercial,''),
                    NULLIF(razon_social,''),
                    CONCAT(TRIM(apellido),' ',TRIM(nombres))
                ) asc
            ")
            ->get([
                'id','rubro_id','nombre_comercial','razon_social','apellido','nombres',
                'telefono','fecha_vto','domicilio_comercio'
            ])
            ->map(function ($r) {
                $fantasia = $r->nombre_comercial ?: '—';
                $titular  = $r->razon_social ?: trim(($r->apellido ?? '').' '.($r->nombres ?? ''));
                $titular  = $titular !== '' ? $titular : '—';

                $telsRel = $r->relationLoaded('telefonos') ? $r->telefonos->pluck('telefono')->filter()->all() : [];
                $telefonos = !empty($telsRel)
                    ? implode(' · ', $telsRel)
                    : (trim((string)($r->telefono ?? '')) ?: '—');

                $sub = '—';
                if ($r->relationLoaded('rubros') && $r->rubros->count()) {
                    $sub = $r->rubros->first()->subrubro ?? '—';
                } elseif ($r->relationLoaded('rubro')) {
                    $sub = optional($r->rubro)->subrubro ?? '—';
                }

                $vto = $r->fecha_vto ? Carbon::parse($r->fecha_vto)->format('Y-m-d') : '—';
                $dir = $r->domicilio_comercio ?: '—';

                return [
                    'fantasia'  => $fantasia,
                    'titular'   => $titular,
                    'telefonos' => $telefonos,
                    'vto'       => $vto,
                    'direccion' => $dir,
                    'subrubro'  => $sub,
                ];
            });

        $pdf = Pdf::loadView('pdf.reporte-habilitaciones', [
            'titulo'       => 'Reporte de Habilitaciones Comerciales',
            'desde'        => $this->desde,
            'hasta'        => $this->hasta,
            'filtros'      => $filtros,
            'items'        => $items,
            'proximosDias' => $this->proximos_vtos,
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'reporte_habilitaciones_'.now()->format('Ymd_His').'.pdf'
        );
    }
    // ---------- fin exportar PDF ----------

    public function getListadoGeneralProperty()
    {
        return $this->base()
            ->with([
                'rubro:id,subrubro',
                'rubros:id,subrubro',
                'estadoModel:codigo,nombre'
            ])
            ->orderBy('razon_social')
            ->paginate(15);
    }

    /** Agrupación por SUBRUBRO (principal) */
    public function getPorRubroProperty()
    {
        $base  = $this->base();
        $total = (clone $base)->count();

        $rows = (clone $base)
            ->join('rubros','rubros.id','=','ubicaciones.rubro_id')
            ->groupBy('rubros.id','rubros.subrubro')
            ->orderBy('cantidad','desc')
            ->get([
                'rubros.id',
                'rubros.subrubro',
                DB::raw('COUNT(*) as cantidad'),
            ])->map(function($r) use ($total){
                $r->porcentaje = $total ? round(($r->cantidad*100)/$total,2) : 0;
                return $r;
            });

        return ['total'=>$total, 'items'=>$rows];
    }

    public function getPorEstadoProperty()
    {
        $hoy = Carbon::today()->toDateString();
        $base = $this->base();

        $vigentes = (clone $base)->where('estado','vigente')->whereDate('fecha_vto','>=',$hoy)->count();
        $vencidos = (clone $base)->where('estado','vigente')->whereDate('fecha_vto','<',$hoy)->count();
        $tramite  = (clone $base)->where('estado','entramite')->count();
        $claus    = (clone $base)->where('estado','irregular')->count();

        $total = $vigentes + $vencidos + $tramite + $claus;
        $pct   = fn($n) => $total ? round(($n*100)/$total,2) : 0;

        return [
            'total'     => $total,
            'vigentes'  => ['n'=>$vigentes, 'pct'=>$pct($vigentes)],
            'vencidos'  => ['n'=>$vencidos, 'pct'=>$pct($vencidos)],
            'tramite'   => ['n'=>$tramite,  'pct'=>$pct($tramite)],
            'claus'     => ['n'=>$claus,    'pct'=>$pct($claus)],
        ];
    }

    public function getHabilitadosPorMesProperty()
    {
        $q = $this->base()
            ->where('estado','vigente')
            ->when($this->desde, fn($q)=>$q->whereDate('fecha_alta','>=',$this->desde))
            ->when($this->hasta, fn($q)=>$q->whereDate('fecha_alta','<=',$this->hasta));

        return $q->selectRaw("YEAR(fecha_alta) as anio, MONTH(fecha_alta) as mes, COUNT(*) as cantidad")
                ->groupBy('anio','mes')
                ->orderBy('anio')->orderBy('mes')
                ->get();
    }

    public function getBajasPorMesProperty()
    {
        $q = $this->base()
            ->where('estado','baja')
            ->when($this->desde, fn($q)=>$q->whereDate('fecha_baja','>=',$this->desde))
            ->when($this->hasta, fn($q)=>$q->whereDate('fecha_baja','<=',$this->hasta));

        return $q->selectRaw("YEAR(fecha_baja) as anio, MONTH(fecha_baja) as mes, COUNT(*) as cantidad")
                ->groupBy('anio','mes')
                ->orderBy('anio')->orderBy('mes')
                ->get();
    }

    public function getProximosAVencerProperty()
    {
        $desde = Carbon::today();
        $hasta = Carbon::today()->addDays($this->proximos_vtos);

        return $this->base()
            ->where('estado','vigente')
            ->whereBetween('fecha_vto', [$desde->toDateString(), $hasta->toDateString()])
            ->with(['rubro:id,subrubro'])
            ->orderBy('fecha_vto')
            ->take(200)
            ->get([
                'id','razon_social','nombre_comercial','estado','fecha_vto','rubro_id'
            ]);
    }

    public function render()
    {
        return view('livewire.comercio.reportes', [
            'rubroOpts' => $this->rubroOpts,
        ])->layout('admin.layouts.app');
    }
}
