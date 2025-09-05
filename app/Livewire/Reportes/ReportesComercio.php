<?php

namespace App\Livewire\Reportes;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Ubicacion;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportesComercio extends Component
{
    public $rubro_id = '';
    public $estado = '';
    public $desde;
    public $hasta;
    public $proximos_vtos = 30;

    public function mount()
    {
        $this->desde = now()->startOfYear()->format('Y-m-d');
        $this->hasta = now()->format('Y-m-d');
    }

    #[On('exportar-pdf')]
    public function exportarPdf()
    {
        $items = Ubicacion::with(['rubro','estadoModel'])
            ->when($this->rubro_id, fn($q) => $q->where('rubro_id', $this->rubro_id))
            ->when($this->estado,   fn($q) => $q->where('estado',   $this->estado))
            ->when($this->desde,    fn($q) => $q->whereDate('created_at','>=',$this->desde))
            ->when($this->hasta,    fn($q) => $q->whereDate('created_at','<=',$this->hasta))
            ->orderBy('nombre_comercial')
            ->get()
            ->map(function($r){
                $nombre = $r->nombre_comercial
                    ?: ($r->razon_social ?: trim(($r->apellido ?? '').' '.($r->nombres ?? '')) ?: '-');
                return [
                    'nombre'    => $nombre,
                    'estado'    => $r->estadoModel->descripcion ?? $r->estado ?? '-',
                    'subrubro'  => optional($r->rubro)->subrubro ?? '-',
                    'vto'       => $r->fecha_vto ? \Illuminate\Support\Carbon::parse($r->fecha_vto)->format('Y-m-d') : '—',
                ];
            });

        $pdf = Pdf::loadView('pdf.reporte-habilitaciones', [
            'titulo'       => 'Reporte de Habilitaciones Comerciales',
            'desde'        => $this->desde,
            'hasta'        => $this->hasta,
            'items'        => $items,
            'proximosDias' => $this->proximos_vtos,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('reporte_habilitaciones_'.now()->format('Ymd_His').'.pdf');
    }

    public function render()
    {
        return view('livewire.reportes.comercio');
    }
}

