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

    public bool $solo_clausurados = false;
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
        ->when($this->estado,   fn($q) => $q->where('estado', $this->estado))
        ->when($this->solo_clausurados, fn($q) => $q->where('situacion', 'clausurado'));
    }

    // ---------- EXPORTAR PDF ----------
    public function exportarPdf()
    {
        $estadoVisual = function (?string $e) {
            return match ($e) {
                'entramite'  => '021/90',
                'irregular'  => '032/01',
                '040'        => '040/25',
                'baja'       => 'Baja',
                'baja_oficio'=> 'Baja de oficio',
                'sin_efecto' => 'Expediente sin efecto',
                null, ''     => 'Todos',
                default      => $e,
            };
        };

        $subrubroNom = $this->rubro_id
            ? optional(Rubro::find($this->rubro_id))->subrubro
            : null;

        $filtros = [
            'Subrubro'               => $subrubroNom ?: 'Todos',
            'Estado'                 => $estadoVisual($this->estado),
            'Desde'                  => $this->desde ?: '—',
            'Hasta'                  => $this->hasta ?: '—',
            'Próx. a vencer (días)'  => (string)($this->proximos_vtos ?? 30),
            'Sólo clausurados'       => $this->solo_clausurados ? 'Sí' : 'No',
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
        $base = $this->base();

        $entramite   = (clone $base)->where('estado', 'entramite')->count();
        $vigente     = (clone $base)->where('estado', 'vigente')->count();
        $irregular   = (clone $base)->where('estado', 'irregular')->count();
        $estado040   = (clone $base)->where('estado', '040')->count();
        $baja        = (clone $base)->where('estado', 'baja')->count();
        $bajaOficio  = (clone $base)->where('estado', 'baja_oficio')->count();
        $sinEfecto   = (clone $base)->where('estado', 'sin_efecto')->count();

        $clausurados = (clone $base)->where('situacion','clausurado')->count();

        $total = $entramite + $vigente + $irregular + $estado040 + $baja + $bajaOficio + $sinEfecto;
        $pct = fn (int $n) => $total ? round(($n * 100) / $total, 2) : 0;

        return [
            'total'        => $total,
            'entramite'    => ['n' => $entramite,  'pct' => $pct($entramite)],
            'vigente'      => ['n' => $vigente,    'pct' => $pct($vigente)],
            'irregular'    => ['n' => $irregular,  'pct' => $pct($irregular)],
            '040'          => ['n' => $estado040,  'pct' => $pct($estado040)],
            'baja'         => ['n' => $baja,       'pct' => $pct($baja)],
            'baja_oficio'  => ['n' => $bajaOficio, 'pct' => $pct($bajaOficio)],
            'sin_efecto'   => ['n' => $sinEfecto,  'pct' => $pct($sinEfecto)],
            'clausurados'  => ['n' => $clausurados,'pct' => $pct($clausurados)],
        ];
    }


    public function scopeClausurados($q)
    {
        return $q->where('situacion', 'clausurado');
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
            ->whereIn('estado', ['vigente','040']) // <- incluir 040
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
