<?php

namespace App\Livewire\Reportes;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Ubicacion;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportesComercio extends Component
{
    public $rubro_id = '';
    public $cambio = '';
    public $rubroGeneral = '';
    public $estado = '';
    public $desde;
    public $hasta;
    public $proximos_vtos = 30;
    public bool $solo_clausurados = false;

    /**
     * Query base (todos los filtros aplicados)
     */
    private function base()
    {
        return Ubicacion::with(['rubro','rubros','estadoModel'])

            // Rubro general (nuevo)
            ->when($this->rubroGeneral !== '', function($q) {
                $q->whereHas('rubro', function($qr) {
                    $qr->where('rubro_general', $this->rubroGeneral);
                });
            })

            // Rubro simple (ID)
            ->when($this->rubro_id, function($q) {
                $q->where('rubro_id', $this->rubro_id);
            })

            // Estado (021/032/040/baja/etc.)
            ->when($this->estado, function($q) {
                $q->where('estado', $this->estado);
            })

            // Cambio → filtra por estado_label
            ->when($this->cambio, function($q) {
                $q->where('estado_label', 'like', "%{$this->cambio}%");
            })

            // Clausura
            ->when($this->solo_clausurados, function($q) {
                $q->where('situacion', 'clausurado');
            })

            // Fecha desde / hasta
            ->when($this->desde, function($q) {
                $q->whereDate('fecha_alta', '>=', $this->desde);
            })
            ->when($this->hasta, function($q) {
                $q->whereDate('fecha_alta', '<=', $this->hasta);
            });
    }


    /**
     * Listado principal
     */
    public function getListadoGeneralProperty()
    {
        return $this->base()
            ->orderBy('nombre_comercial')
            ->paginate(15);
    }


    /**
     * Fechas por defecto
     */
    public function mount()
    {
        $this->desde = now()->startOfYear()->format('Y-m-d');
        $this->hasta = now()->format('Y-m-d');
    }


    /**
     * Exportación PDF
     */
    #[On('exportar-pdf')]
    public function exportarPdf()
    {
        $items = $this->base()   // ← ahora respeta TODOS los filtros
            ->orderBy('nombre_comercial')
            ->get()
            ->map(function($r){
                $nombre = $r->nombre_comercial
                    ?: ($r->razon_social ?: trim(($r->apellido ?? '').' '.($r->nombres ?? '')) ?: '-');

                return [
                    'nombre'    => $nombre,
                    'estado'    => $r->estadoModel->descripcion ?? $r->estado ?? '-',
                    'subrubro'  => optional($r->rubro)->subrubro ?? '-',
                    'vto'       => $r->fecha_vto
                        ? \Illuminate\Support\Carbon::parse($r->fecha_vto)->format('Y-m-d')
                        : '—',
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


    /**
     * Render
     */
    public function render()
    {
        return view('livewire.reportes.comercio', [
            'listado' => $this->listadoGeneral,
        ]);
    }
}

