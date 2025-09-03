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

    // Form
    public $titulo, $descripcion, $estado = 'En Proceso', $archivo;
    public $tipo_acta = null;

    // Edición
    public ?int $movimientoIdEdit = null;
    public ?string $archivoActual = null; // ruta del archivo actual (para mostrar/ conservar/ borrar)

    protected $rules = [
        'titulo'      => 'required|string|min:2|max:190',
        'descripcion' => 'nullable|string|max:5000',
        'estado'      => 'required|string|in:En Proceso,Observado,Completo,Rechazado,Archivado,Cancelado',
        'archivo'     => 'nullable|file|max:2048',
        'tipo_acta'   => 'nullable|in:asesoramiento,notificacion,inspeccion,infraccion',
    ];

    // Abrir modal desde afuera
    protected $listeners = ['abrirModalMovimientos'];

    public function render()
    {
        return view('livewire.comercio.movimiento-modal');
    }

    public function abrirModalMovimientos($ubicacionId)
    {
        $this->ubicacion = Ubicacion::findOrFail($ubicacionId);

        // reset formulario (y edición)
        $this->resetForm();

        $this->cargarMovimientos();

        $this->dispatch('mostrar-modal-movimientos');
    }

    /** === CREAR o ACTUALIZAR según haya $movimientoIdEdit === */
    public function guardarMovimiento()
    {
        $this->validate();

        // Manejo de archivo: calcular ruta final según si hay upload nuevo
        $archivoPath = $this->archivoActual; // conservar por defecto
        if ($this->archivo) {
            // si había archivo anterior y suben otro, borrar el anterior
            if ($this->archivoActual) {
                Storage::disk('public')->delete($this->archivoActual);
            }
            $nombreLimpio = Str::slug($this->titulo);
            $extension    = $this->archivo->getClientOriginalExtension();
            $nombreFinal  = "ubicacion_{$this->ubicacion->id}_{$nombreLimpio}.".$extension;
            $archivoPath = $this->archivo->storePubliclyAs('movimientos', $nombreFinal, 'public');
        }

        // CREATE
        if (!$this->movimientoIdEdit) {
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

            $this->dispatch('toast', type: 'success', message: 'Movimiento creado.');
        }
        // UPDATE
        else {
            $mov = Movimiento::where('id', $this->movimientoIdEdit)
                ->where('ubicacion_id', $this->ubicacion->id)
                ->firstOrFail();

            $mov->update([
                'tipo_acta'   => $this->tipo_acta,
                'titulo'      => Str::title($this->titulo),
                'descripcion' => $this->descripcion ? Str::title($this->descripcion) : '',
                'estado'      => $this->estado,
                'archivo'     => $archivoPath, // mantener/ reemplazar según arriba
                // 'fecha'    => $mov->fecha, // si no querés tocar la fecha original
            ]);

            $this->dispatch('toast', type: 'success', message: 'Movimiento actualizado.');
        }

        // refrescar lista y reset form
        $this->cargarMovimientos();
        $this->resetForm(keepModalOpen: true);
    }

    /** Precargar datos para editar (se llama directo desde Blade) */
    public function editarMovimiento(int $movId): void
    {
        $mov = Movimiento::where('id', $movId)
            ->where('ubicacion_id', $this->ubicacion->id)
            ->firstOrFail();

        $this->movimientoIdEdit = $mov->id;
        $this->titulo           = $mov->titulo;
        $this->descripcion      = $mov->descripcion;
        $this->estado           = $mov->estado ?? 'En Proceso';
        $this->tipo_acta        = $mov->tipo_acta;
        $this->archivo          = null;                // limpiar input file
        $this->archivoActual    = $mov->archivo;       // ruta actual (public/movimientos/xxx)

        // Mantener modal abierto (ya lo está), opcionalmente emite un evento para focus
        $this->dispatch('toast', type: 'info', message: 'Editando movimiento…');
    }

    public function cancelarEdicion(): void
    {
        $this->resetForm(keepModalOpen: true);
        $this->dispatch('toast', type: 'info', message: 'Edición cancelada.');
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

    private function resetForm(bool $keepModalOpen = false): void
    {
        $this->reset(['titulo','descripcion','archivo','tipo_acta']);
        $this->estado            = 'En Proceso';
        $this->movimientoIdEdit  = null;
        $this->archivoActual     = null;

        // si quisieras cerrar modal acá, emitirías un evento específico del modal de movimientos
        if (!$keepModalOpen) {
            // $this->dispatch('cerrar-modal-movimientos');
        }
    }
}
