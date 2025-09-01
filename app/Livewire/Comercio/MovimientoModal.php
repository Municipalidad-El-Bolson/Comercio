<?php

namespace App\Livewire\Comercio;

use App\Models\Movimiento;
use App\Models\Ubicacion;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MovimientoModal extends Component
{
    use WithFileUploads;

    public $ubicacion;
    public $movimientos = [];

    public $titulo, $descripcion, $estado = 'En Proceso', $archivo;
    public $tipo_acta = null;

    protected $rules = [
        'titulo'      => 'required|string',
        'descripcion' => 'nullable|string',
        'estado'      => 'required|string',
        'archivo'     => 'nullable|file|max:2048',
        'tipo_acta'   => 'nullable|in:asesoramiento,notificacion,inspeccion,infraccion',
    ];

    protected $listeners = ['abrirModalMovimientos'];

    public function render()
    {
        return view('livewire.comercio.movimiento-modal');
    }

    public function abrirModalMovimientos($ubicacionId)
    {
        $this->ubicacion = Ubicacion::findOrFail($ubicacionId);

        $this->reset(['titulo', 'descripcion', 'archivo']);
        $this->estado = 'En Proceso';
        $this->tipo_acta = null;

        $this->cargarMovimientos();

        $this->dispatch('mostrar-modal-movimientos');
    }

    public function guardarMovimiento()
    {
        $this->validate();

        $archivoPath = null;
        if ($this->archivo) {
            $nombreLimpio = Str::slug($this->titulo);
            $extension    = $this->archivo->getClientOriginalExtension();
            $nombreFinal  = "ubicacion_{$this->ubicacion->id}_{$nombreLimpio}.".$extension;
            $archivoPath  = $this->archivo->storeAs('movimientos', $nombreFinal, 'public');
        }

        Movimiento::create([
            'ubicacion_id' => $this->ubicacion->id,
            'tipo'         => 'acta',
            'tipo_acta'    => $this->tipo_acta,  
            'titulo'       => Str::title($this->titulo),
            'descripcion'  => $this->descripcion ? Str::title($this->descripcion) : '',
            'estado'       => $this->estado,
            'archivo'      => $archivoPath, 
            'fecha'        => now(),
        ]);

        $this->reset(['titulo', 'descripcion', 'archivo','tipo_acta']);
        $this->estado = 'En Proceso';

        $this->cargarMovimientos();

        $this->dispatch('hide-form', ['message' => 'Movimiento guardado con éxito.']);
    }

    protected function cargarMovimientos(): void
    {
        if (!$this->ubicacion) return;

        $this->movimientos = $this->ubicacion->movimientos()
            ->where('tipo', 'acta')
            ->latest()
            ->get();
    }

    public function eliminarMovimiento(int $movId)
    {
        $mov = Movimiento::where('id', $movId)
            ->where('ubicacion_id', $this->ubicacion->id)
            ->firstOrFail();

        if ($mov->archivo) {
            Storage::disk('public')->delete($mov->archivo);
        }

        $mov->delete();

        $this->cargarMovimientos();

        $this->dispatch('toast', type: 'success', message: 'Movimiento eliminado');
    }
}
