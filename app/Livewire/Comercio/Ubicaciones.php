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
            'persona_tipo' => 'fisica',
            'estado' => 'vigente',
            'documentos' => [
                // Generales
                'doc_libre_deuda_municipal' => false,
                'doc_planeamiento_urbano' => false,
                'doc_solicitud_habilitacion_pago' => false,
                // Físicas
                'doc_afip_constancia_fisica' => false,
                'doc_fotocopia_dni' => false,
                'doc_constancia_recaudacion' => false,
                // Jurídicas
                'doc_afip_constancia_juridica' => false,
                'doc_acta_constitucion' => false,
                'doc_contrato_societario' => false,
                'doc_docs_representantes' => false,
                // Otros
                'doc_comprobante_uso_local' => false,
            ],
        ];
        $this->showEditModal = false;
        $this->dispatch('show-form');
    }

    public function createCliente()
    {
        $rules = [
            'persona_tipo'          => 'required|in:fisica,juridica',
            'apellido'              => 'nullable|string',
            'nombres'               => 'nullable|string',
            'razon_social'          => 'nullable|string',
            'dni_cuit'              => 'required|string',
            'rubro_id'              => 'required|exists:rubros,id',
            'domicilio_responsable' => 'required|string',
            'correo'                => 'nullable|email',
            'telefono'              => 'nullable|string',
            'nombre_comercial'      => 'nullable|string',
            'domicilio_comercio'    => 'required|string',
            'nomenclatura'          => 'nullable|string',
            'observaciones'         => 'nullable|string',
            'estado'                => 'required|in:vigente,irregular,entramite',

            // Documentos (boolean)
            'documentos.doc_libre_deuda_municipal'   => 'boolean',
            'documentos.doc_planeamiento_urbano'     => 'boolean',
            'documentos.doc_solicitud_habilitacion_pago' => 'boolean',
            'documentos.doc_afip_constancia_fisica'  => 'boolean',
            'documentos.doc_fotocopia_dni'           => 'boolean',
            'documentos.doc_constancia_recaudacion'  => 'boolean',
            'documentos.doc_afip_constancia_juridica'=> 'boolean',
            'documentos.doc_acta_constitucion'       => 'boolean',
            'documentos.doc_contrato_societario'     => 'boolean',
            'documentos.doc_docs_representantes'     => 'boolean',
            'documentos.doc_comprobante_uso_local'   => 'boolean',
        ];

        // Validación condicional:
        if (($this->state['persona_tipo'] ?? 'fisica') === 'fisica') {
            $rules['apellido'] = 'required|string';
            $rules['nombres']  = 'required|string';
            // 'razon_social' puede ser null
        } else { // juridica
            $rules['razon_social'] = 'required|string';
            // Apellido/Nombres pueden ser null
        }

        $validated = Validator::make($this->state, $rules)->validate();

        // Formateos (opcional)
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $campo) {
            if (!empty($validated[$campo])) {
                $validated[$campo] = \Illuminate\Support\Str::title($validated[$campo]);
            }
        }

        // Crear Ubicacion
        $documentos = $validated['documentos'] ?? [];
        unset($validated['documentos']);

        $ubic = \App\Models\Ubicacion::create($validated);

        // Crear checklist documentos
        $ubic->documentos()->create($documentos);

        // Reset UI
        $this->resetPage();
        $this->reset('state');
        $this->dispatch('hide-form', ['message' => 'Comercio creado correctamente.']);
    }

    public function updateComercio()
    {
        $rules = [
            'persona_tipo'          => 'required|in:fisica,juridica',
            'apellido'              => 'nullable|string',
            'nombres'               => 'nullable|string',
            'razon_social'          => 'nullable|string',
            'dni_cuit'              => 'required|string',
            'rubro_id'              => 'required|exists:rubros,id',

            'domicilio_responsable' => 'required|string',
            'correo'                => 'nullable|email',
            'telefono'              => 'nullable|string',
            'nombre_comercial'      => 'nullable|string',
            'domicilio_comercio'    => 'required|string',
            'nomenclatura'          => 'nullable|string',
            'observaciones'         => 'nullable|string',

            'estado'                => 'required|in:vigente,irregular,entramite',
            'situacion'             => 'required|in:alta,baja',
            'fecha_alta'            => 'nullable|date',
            'fecha_baja'            => 'nullable|date',
        ];

        // Condiciones extra
        if (($this->state['persona_tipo'] ?? 'fisica') === 'fisica') {
            $rules['apellido'] = 'required|string';
            $rules['nombres']  = 'required|string';
        } else {
            $rules['razon_social'] = 'required|string';
        }

        if (($this->state['situacion'] ?? 'alta') === 'baja') {
            $rules['fecha_baja'] = 'required|date';
        } else {
            $rules['fecha_alta'] = 'required|date';
        }

        $validatedData = Validator::make($this->state, $rules)->validate();

        // Formateo de mayúsculas/minúsculas
        if (!empty($validatedData['razon_social'])) {
            $validatedData['razon_social'] = Str::title($validatedData['razon_social']);
        }
        if (!empty($validatedData['apellido'])) {
            $validatedData['apellido'] = Str::title($validatedData['apellido']);
        }
        if (!empty($validatedData['nombres'])) {
            $validatedData['nombres'] = Str::title($validatedData['nombres']);
        }

        // Sufijo para domicilio del comercio
        $sufijo = ', R8430 El Bolsón, Río Negro';
        $direccion = Str::title($validatedData['domicilio_comercio']);
        $validatedData['domicilio_comercio'] = Str::endsWith($direccion, $sufijo)
            ? $direccion
            : $direccion . $sufijo;

        // Actualizar registro
        $this->ubicacion->update($validatedData);

        // Recargar listado
        $this->ubicaciones = Ubicacion::where('razon_social', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('razon_social', 'asc')
            ->get();

        $this->dispatch('hide-form', ['message' => 'Registro actualizado correctamente']);
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
