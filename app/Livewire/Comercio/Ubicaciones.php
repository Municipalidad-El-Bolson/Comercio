<?php

namespace App\Livewire\Comercio;

use App\Livewire\Admin\AdminComponent;
use App\Models\Rubro;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\WithPagination;

class Ubicaciones extends AdminComponent
{

    use WithPagination;

    public $searchTerm = '';

    public $state = [];

    public $ubicacion = null;

    public $showEditModal = false;

    public $count = 2;

    public $variable = Null;

    public $rubros = [];

    public function mount()
    {
        $this->rubros = Rubro::orderBy('rubro_madre', 'asc')->get();
    }

    public function updatingSearchTerm()
    {
        $this->resetPage();
    }

    public function editaComercio(Ubicacion $ubicacion)
    {
        $this->showEditModal = true;

        $this->ubicacion = $ubicacion;

        $this->state = $ubicacion->toArray();

        // dd($this->state);

        $this->dispatch('show-form');
    }

    public function render()
    {
        $ubicaciones = Ubicacion::where('razon_social', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('razon_social', 'asc')
            ->paginate(10);

        return view('livewire.comercio.ubicaciones', [
            'ubicaciones' => $ubicaciones,
            'ubicaciones' => $ubicaciones,
            'rubros'      => $this->rubros,
        ])->layout('admin.layouts.app');
    }

    public function nuevoComercio()
    {
        $this->reset('state', 'ubicacion');

        $this->state = [
            'estado' => 'normal',
            'tipo' => '<i class="fas fa-store"></i>',
        ];

        $this->showEditModal = false;

        $this->dispatch('show-form'); // muestra el modal (lo mismo que con editar)
    }

    public function createCliente()
    {
        $validatedData = Validator::make($this->state, [
            'razon_social' => 'required|string',
            'apellido'     => 'required|string',
            'nombres'      => 'required|string',
            'dni'          => 'required|integer',
            'direccion'    => 'required|string',
            'rubro_id'     => 'required|exists:rubros,id',
            'tipo'         => 'required|string',
            'estado'       => 'required|in:normal,irregular,faltadoc',
        ])->validate();

        // Formateo de campos capitalizables
        foreach (['razon_social', 'direccion', 'apellido', 'nombres'] as $campo) {
            if (isset($validatedData[$campo])) {
                $validatedData[$campo] = Str::title($validatedData[$campo]);
            }
        }

        $validatedData['direccion'] = Str::title($validatedData['direccion']) . ', R8430 El Bolsón, Río Negro';

        // Crear nuevo registro
        Ubicacion::create($validatedData);

        // Refrescar listado
        $this->resetPage(); // vuelve a la página 1 si estás paginando
        $this->reset('state');
        $this->dispatch('hide-form', ['message' => 'Comercio creado correctamente.']);
    }


    public function updateComercio()
    {
        $validatedData = Validator::make($this->state, [
            'razon_social' => 'required|string',
            'apellido'     => 'required|string',
            'nombres'      => 'required|string',
            'dni'          => 'required|integer',
            'direccion'    => 'required|string',
            'rubro_id'     => 'required|exists:rubros,id',
        ])->validate();

        $validatedData['razon_social'] = Str::title($validatedData['razon_social']);
        $sufijo = ', R8430 El Bolsón, Río Negro';
        $direccion = Str::title($validatedData['direccion']);
        $validatedData['direccion'] = Str::endsWith($direccion, $sufijo)
            ? $direccion
            : $direccion . $sufijo;
        $validatedData['apellido'] = Str::title($validatedData['apellido']);
        $validatedData['nombres'] = Str::title($validatedData['nombres']);

        $this->ubicacion->update($validatedData);

        $this->ubicaciones = Ubicacion::where('razon_social', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('razon_social', 'asc')
            ->get();


        $this->dispatch('hide-form', ['message' => 'Registro actualizado correctamente']);

        // $this->dispatch('toast', ['type' => 'error','message' => '❌ No se pudo actualizar el cliente']);
    }

    public function resetForm()
    {
        $this->state = [];
        $this->ubicacion = null;
        $this->showEditModal = false;
    }

    public function mostrarMovimientos($id)
    {
        $this->dispatch('abrirModalMovimientos', $id);
    }
}
