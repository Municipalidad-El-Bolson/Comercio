<?php

namespace App\Livewire\Comercio;

use App\Models\Movimiento;
use App\Models\Ubicacion;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class MovimientoModal extends Component
{
    use WithFileUploads;

    public $ubicacion;
    public $movimientos = [];

    public $titulo, $descripcion, $estado, $archivo;

    protected $rules = [
        'titulo' => 'required|string',
        'descripcion' => 'nullable|string',
        'estado' => 'nullable|string',
        'archivo' => 'nullable|file|max:2048',
    ];

    protected $listeners = ['abrirModalMovimientos', 'deleteConfirmed' => 'deleteConfirmed'];


    public function render()
    {
        return view('livewire.comercio.movimiento-modal');
    }

    public function abrirModalMovimientos($ubicacionId)
    {
        $this->ubicacion = Ubicacion::findOrFail($ubicacionId);
        $this->movimientos = $this->ubicacion->movimientos()->latest()->get();

        $this->reset(['titulo', 'descripcion', 'estado', 'archivo']);
        $this->estado = 'En Proceso'; // valor por defecto

        $this->dispatch('mostrar-modal-movimientos'); // este evento se captura con JS para abrir el modal
    }

    public function guardarMovimiento()
    {
        $this->validate();

        $archivoPath = null;

        if ($this->archivo) {
            $nombreLimpio = Str::slug($this->titulo); // ej: "certificado-inscripcion"
            $extension = $this->archivo->getClientOriginalExtension();
            $nombreFinal = "ubicacion_{$this->ubicacion->id}_{$nombreLimpio}." . $extension;

            $archivoPath = $this->archivo->storeAs('movimientos', $nombreFinal, 'public');
        }

        $this->titulo = Str::title($this->titulo);
        $this->descripcion = Str::title($this->descripcion);

        $this->ubicacion->movimientos()->create([
            'titulo' => $this->etapaActual ?? 'Etapa sin título',
            'descripcion' => $this->descripcion,
            'estado' => $this->estado,
            'archivo' => $archivoPath,
        ]);

        // Refrescar el listado de movimientos
        $this->abrirModalMovimientos($this->ubicacion->id);

        // Mensaje visual
        $this->dispatch('hide-form', ['message' => 'Movimiento guardado con éxito.']);
    }

    public function deleteConfirmed()
    {
        $this->dispatchBrowserEvent('eliminado', ['message' => 'Articulo Eliminado - ']);
    }

    public function showConfirmation($id)
    {

        $this->dispatch('show-delete-confirmation');
    }
}
