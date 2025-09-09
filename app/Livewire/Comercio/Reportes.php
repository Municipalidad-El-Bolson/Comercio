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
    public ?string $mega = null;         // Mega rubro (texto)
    public ?string $madre = null;        // Rubro madre (texto)
    public ?string $estado = null;
    public ?string $desde = null;
    public ?string $hasta = null;
    public int $proximos_vtos = 30;

    // Opciones
    public array $rubros = [];           // lista de subrubros
    public array $megas = [];            // lista de mega rubros (strings)

    public function mount()
    {
        abort_unless(Gate::allows('access-admin'), 403);

        // Opciones para selects
        $this->rubros = Rubro::orderBy('subrubro')->get(['id','subrubro'])->toArray();
        $this->megas  = Rubro::query()
            ->select('mega_rubro')->whereNotNull('mega_rubro')
            ->distinct()->orderBy('mega_rubro')->pluck('mega_rubro')->toArray();

        $this->desde = Carbon::now()->startOfYear()->toDateString();
        $this->hasta = Carbon::now()->toDateString();
    }

    /**
     * Query base con filtros aplicados
     * (usamos subconsultas para mega/madre y evitamos joins innecesarios)
     */
    private function base()
    {
        return Ubicacion::query()
            ->when($this->rubro_id, fn($q) => $q->where('rubro_id', $this->rubro_id))
            ->when($this->mega, function ($q) {
                $ids = Rubro::where('mega_rubro', $this->mega)->pluck('id');
                $q->whereIn('rubro_id', $ids);
            })
            ->when($this->madre, function ($q) {
                $ids = Rubro::where('rubro_madre', $this->madre)->pluck('id');
                $q->whereIn('rubro_id', $ids);
            })
            ->when($this->estado, fn($q) => $q->where('estado', $this->estado));
    }

    // ---------- EXPORTAR PDF ----------

    public function exportarPdf()
    {
        // Armo etiquetas de filtros “humanas”
        $subrubroNom = $this->rubro_id
            ? optional(\App\Models\Rubro::find($this->rubro_id))->subrubro
            : null;

        $filtros = [
            'Mega rubro'   => $this->mega ?: 'Todos',
            'Rubro madre'  => $this->madre ?: 'Todos',
            'Subrubro'     => $subrubroNom ?: 'Todos',
            'Estado'       => $this->estado ?: 'Todos',
            'Desde'        => $this->desde ?: '—',
            'Hasta'        => $this->hasta ?: '—',
            'Próx. a vencer (días)' => (string)($this->proximos_vtos ?? 30),
        ];

        // Traigo datos + relaciones para armar las columnas pedidas
        $items = $this->base()
            ->with([
                'rubro:id,subrubro',     // legado (1 rubro)
                'rubros' => function ($q) { // m2m (si existe)
                    $q->select('rubros.id','rubros.subrubro');
                },
                'telefonos:id,ubicacion_id,telefono', // 1-N (si existe)
            ])
            ->orderByRaw("COALESCE(NULLIF(nombre_comercial,''), NULLIF(razon_social,''), CONCAT(TRIM(apellido),' ',TRIM(nombres))) asc")
            ->get([
                'id','rubro_id','nombre_comercial','razon_social','apellido','nombres',
                'telefono','fecha_vto','domicilio_comercio'
            ])
            ->map(function ($r) {
                // Fantasía
                $fantasia = $r->nombre_comercial ?: '—';

                // Titular (razón social o Apellido + Nombres)
                $titular = $r->razon_social
                    ?: trim(($r->apellido ?? '').' '.($r->nombres ?? ''));
                $titular = $titular !== '' ? $titular : '—';

                // Teléfonos (priorizo relación; si no hay, uso campo legado)
                $telsRel = method_exists($r, 'telefonos') ? $r->telefonos->pluck('telefono')->filter()->all() : [];
                $telefonos = !empty($telsRel)
                    ? implode(' · ', $telsRel)
                    : (trim((string)($r->telefono ?? '')) ?: '—');

                // Subrubro (si hay m2m, tomo el 1° por orden de pivot; si no, legado)
                $sub = '—';
                if (method_exists($r, 'rubros') && $r->rubros->count()) {
                    // si tenés columna pivot.orden, podés ordenar acá:
                    $sub = $r->rubros->first()->subrubro ?? '—';
                } elseif ($r->relationLoaded('rubro')) {
                    $sub = optional($r->rubro)->subrubro ?? '—';
                }

                // Vto + Dirección
                $vto = $r->fecha_vto ? \Illuminate\Support\Carbon::parse($r->fecha_vto)->format('Y-m-d') : '—';
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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reporte-habilitaciones', [
            'titulo'       => 'Reporte de Habilitaciones Comerciales',
            'desde'        => $this->desde,
            'hasta'        => $this->hasta,
            'filtros'      => $filtros,     // << NUEVO: para mostrar en el PDF
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
            ->with(['rubro:id,subrubro', 'estadoModel:codigo,nombre'])
            ->orderBy('razon_social')
            ->paginate(15);
    }

    /** Agrupación por SUBRUBRO (existente) */
    public function getPorRubroProperty()
    {
        $base = $this->base();
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

        return [
            'total' => $total,
            'items' => $rows,
        ];
    }

    /** NUEVO: Agrupación por MEGA RUBRO */
    public function getPorMegaRubroProperty()
    {
        $base = $this->base();
        $total = (clone $base)->count();

        $rows = (clone $base)
            ->join('rubros','rubros.id','=','ubicaciones.rubro_id')
            ->groupBy('rubros.mega_rubro')
            ->orderBy('cantidad','desc')
            ->get([
                DB::raw("COALESCE(NULLIF(rubros.mega_rubro,''),'(Sin dato)') as mega"),
                DB::raw('COUNT(*) as cantidad'),
            ])->map(function($r) use ($total){
                $r->porcentaje = $total ? round(($r->cantidad*100)/$total,2) : 0;
                return $r;
            });

        return [
            'total' => $total,
            'items' => $rows,
        ];
    }

    /** NUEVO: Agrupación por RUBRO MADRE */
    public function getPorRubroMadreProperty()
    {
        $base = $this->base();
        $total = (clone $base)->count();

        $rows = (clone $base)
            ->join('rubros','rubros.id','=','ubicaciones.rubro_id')
            ->groupBy('rubros.rubro_madre')
            ->orderBy('cantidad','desc')
            ->get([
                DB::raw("COALESCE(NULLIF(rubros.rubro_madre,''),'(Sin dato)') as madre"),
                DB::raw('COUNT(*) as cantidad'),
            ])->map(function($r) use ($total){
                $r->porcentaje = $total ? round(($r->cantidad*100)/$total,2) : 0;
                return $r;
            });

        return [
            'total' => $total,
            'items' => $rows,
        ];
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
        $pct = fn($n) => $total ? round(($n*100)/$total,2) : 0;

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

    public function updatedMega($value): void
    {
        // Al cambiar Mega, reseteo Madre y Subrubro
        $this->madre = null;
        $this->rubro_id = null;
        $this->resetPage();
    }

    public function updatedMadre($value): void
    {
        // Al cambiar Madre, reseteo Subrubro
        $this->rubro_id = null;
        $this->resetPage();
    }

    private function madreOptions(): array
    {
        return Rubro::query()
            ->when($this->mega, fn($q) => $q->where('mega_rubro', $this->mega))
            ->select('rubro_madre')
            ->whereNotNull('rubro_madre')
            ->distinct()
            ->orderBy('rubro_madre')
            ->pluck('rubro_madre')
            ->toArray();
    }

    private function subrubroOptions(): array
    {
        return Rubro::query()
            ->when($this->mega,  fn($q) => $q->where('mega_rubro',  $this->mega))
            ->when($this->madre, fn($q) => $q->where('rubro_madre', $this->madre))
            ->orderBy('subrubro')
            ->get(['id','subrubro'])
            ->toArray();
    }


    public function render()
    {
        // Opciones dinámicas para selects dependientes
        $madresOpts   = $this->madreOptions();
        $subrubroOpts = $this->subrubroOptions();

        return view('livewire.comercio.reportes', [
            'megas'        => $this->megas,     // ya lo tenías
            'madresOpts'   => $madresOpts,      // NUEVO
            'subrubroOpts' => $subrubroOpts,    // NUEVO
        ])->layout('admin.layouts.app');
    }

}
