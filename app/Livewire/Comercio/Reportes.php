<?php

namespace App\Livewire\Comercio;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\Ubicacion;
use App\Models\Rubro;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use App\Livewire\Admin\AdminComponent;

class Reportes extends Component
{
    use WithPagination;

    public ?int $rubro_id = null;          // subrubro (id de rubros)
    public ?string $estado = null;         // entramite|vigente|irregular|baja
    public ?string $desde = null;          // YYYY-MM-DD
    public ?string $hasta = null;          // YYYY-MM-DD
    public int $proximos_vtos = 30;        // 30|60|90 días

    public array $rubros = [];

    public function mount()
    {
        abort_unless(Gate::allows('access-admin'), 403);
        
        $this->rubros = Rubro::orderBy('subrubro')->get(['id','subrubro'])->toArray();
        $this->desde = Carbon::now()->startOfYear()->toDateString();
        $this->hasta = Carbon::now()->toDateString();
    }

    private function base()
    {
        return Ubicacion::query()
            ->when($this->rubro_id, fn($q) => $q->where('rubro_id', $this->rubro_id))
            ->when($this->estado, fn($q) => $q->where('estado', $this->estado));
    }

    public function getListadoGeneralProperty()
    {
        return $this->base()
            ->with(['rubro:id,subrubro', 'estadoModel:codigo,nombre'])
            ->orderBy('razon_social')
            ->paginate(15);
    }

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
            ->take(200) // evita traer miles
            ->get([
                'id','razon_social','estado','fecha_vto','rubro_id'
            ]);
    }

    public function render()
    {
        return view('livewire.comercio.reportes')->layout('admin.layouts.app');
    }
}
