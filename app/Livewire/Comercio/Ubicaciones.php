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
    public $rubros = [];

    /** Documentos booleanos soportados (clave => default) */
    protected array $docDefaults = [
        // Generales
        'doc_libre_deuda_municipal'      => false,
        'doc_planeamiento_urbano'        => false,
        'doc_solicitud_habilitacion_pago'=> false,
        'doc_comprobante_uso_local'      => false,
        'doc_afip_constancia'            => false,
        'doc_recaudacion_rn'             => false,
        'doc_fotocopia_dni'              => false,
        'doc_comprobante_uso_inmueble'   => false,
        'doc_libre_deuda_tasas_inmueble' => false,
        'doc_aptitud_tecnica_local'      => false,
        'doc_cocap_rhi'                  => false,
        'doc_nota_carteleria_obras'      => false,
        'doc_libro_actas_100'            => false,
        // Jurídicas
        'doc_acta_constitucion'          => false,
        'doc_contrato_societario'        => false,
        'doc_docs_representantes'        => false,
    ];

    public function mount()
    {
        // Para el combo de Rubro en el modal
        $this->rubros = Rubro::select('id','rubro_madre','subrubro')
            ->orderBy('rubro_madre')->orderBy('subrubro')->get();
    }

    public function updatingSearchTerm()
    {
        $this->resetPage();
    }

    public function render()
    {
        // Eager load del rubro para el mapa/listado
        $ubicaciones = Ubicacion::with('rubro')
            ->where('razon_social', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('apellido', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('nombres', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('razon_social')
            ->paginate(10);

        return view('livewire.comercio.ubicaciones', [
            'ubicaciones' => $ubicaciones,
            'rubros'      => $this->rubros,
        ])->layout('admin.layouts.app');
    }

    /** Botón "Nuevo Comercio" */
    public function nuevoComercio()
    {
        abort_unless(auth()->user()->can('ubicaciones.create'), 403);

        $this->reset('state', 'ubicacion');

        $this->state = [
            'persona_tipo'       => 'fisica',   // fisica | juridica
            'estado'             => 'vigente',  // vigente | irregular | entramite
            'situacion'          => 'alta',     // alta | baja
            'fecha_alta'         => null,
            'fecha_baja'         => null,
            // otros campos se completan en el form...
            'documentos'         => $this->docDefaults,
        ];

        $this->showEditModal = false;
        $this->dispatch('show-form');
    }

    /** Editar Comercio (abre modal con datos + documentos) */
    public function editaComercio(Ubicacion $ubicacion)
    {
        abort_unless(auth()->user()->can('ubicaciones.update'), 403);

        $this->showEditModal = true;
        $this->ubicacion = $ubicacion->loadMissing('documentos');

        // Pasar a array el modelo y fusionar los docs con defaults (por si faltan claves)
        $this->state = $this->ubicacion->toArray();
        $docs = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $soloDocs = array_intersect_key($docs, $this->docDefaults);
        $this->state['documentos'] = array_merge($this->docDefaults, $soloDocs);

        $this->dispatch('show-form');
    }

    /** Crear */
    public function createCliente()
    {
        abort_unless(auth()->user()->can('ubicaciones.create'), 403);

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

        // Validación condicional (identidad)
        if (($this->state['persona_tipo'] ?? 'fisica') === 'fisica') {
            $rules['apellido'] = 'required|string';
            $rules['nombres']  = 'required|string';
        } else {
            $rules['razon_social'] = 'required|string';
        }

        // Condicional (situación)
        if (($this->state['situacion'] ?? 'alta') === 'baja') {
            $rules['fecha_baja'] = 'required|date';
        } else {
            $rules['fecha_alta'] = 'required|date';
        }

        // Reglas booleanas para todos los docs
        foreach (array_keys($this->docDefaults) as $key) {
            $rules["documentos.$key"] = 'boolean';
        }

        $validated = Validator::make($this->state, $rules)->validate();

        // Normalizar nombres propios / direcciones
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $campo) {
            if (!empty($validated[$campo])) {
                $validated[$campo] = Str::title($validated[$campo]);
            }
        }

        // Identidad coherente: null donde no aplica
        $esFisica = ($validated['persona_tipo'] ?? 'fisica') === 'fisica';
        if ($esFisica) {
            $validated['razon_social'] = $validated['razon_social'] ?? null;
        } else {
            $validated['apellido'] = $validated['apellido'] ?? null;
            $validated['nombres']  = $validated['nombres']  ?? null;
        }

        // Documento checklist
        $documentos = $validated['documentos'] ?? [];
        unset($validated['documentos']);

        // Crear Ubicacion
        $ubic = Ubicacion::create($validated);

        // Crear/guardar checklist de documentos (hasOne)
        $ubic->documentos()->create(array_merge($this->docDefaults, $documentos));

        // Reset UI
        $this->resetPage();
        $this->reset('state');
        $this->dispatch('hide-form', ['message' => 'Comercio creado correctamente.']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($ubic)
            ->event('created')
            ->withProperties([
                'campos' => $validated,  // o Arr::only(...) si querés menos
            ])
            ->log('Creó una ubicación');
    }

    /** Actualizar */
    public function updateComercio()
    {
        abort_unless(auth()->user()->can('ubicaciones.update'), 403);

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

        foreach (array_keys($this->docDefaults) as $key) {
            $rules["documentos.$key"] = 'boolean';
        }

        $validated = Validator::make($this->state, $rules)->validate();

        // Formateos
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $campo) {
            if (!empty($validated[$campo])) {
                $validated[$campo] = Str::title($validated[$campo]);
            }
        }

        // Identidad coherente
        $esFisica = ($validated['persona_tipo'] ?? 'fisica') === 'fisica';
        if ($esFisica) {
            $validated['razon_social'] = $validated['razon_social'] ?? null;
        } else {
            $validated['apellido'] = $validated['apellido'] ?? null;
            $validated['nombres']  = $validated['nombres']  ?? null;
        }

        // Sufijo para domicilio del comercio (si lo seguís usando)
        if (!empty($validated['domicilio_comercio'])) {
            $sufijo = ', R8430 El Bolsón, Río Negro';
            $dir = $validated['domicilio_comercio'];
            $validated['domicilio_comercio'] = Str::endsWith($dir, $sufijo) ? $dir : $dir . $sufijo;
        }

        // Separar documentos
        $documentos = $validated['documentos'] ?? [];
        unset($validated['documentos']);

        // Guardar Ubicación
        $this->ubicacion->update($validated);

        // Guardar checklist (crea si no existe)
        $this->ubicacion->documentos()->updateOrCreate([], array_merge($this->docDefaults, $documentos));

        // Refrescar listado (opcional: mantener paginación)
        $this->resetPage();

        $this->dispatch('hide-form', ['message' => 'Registro actualizado correctamente']);
    
        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->ubicacion)
            ->withProperties([
                'datos' => $validatedData
            ])
            ->log('Editó un comercio');
    
    }

    /** Botón "Presentó toda la documentación" / "Limpiar" */
    public function marcarTodosLosDocs(bool $valor = true): void
    {
        $this->state['documentos'] = $this->state['documentos'] ?? [];
        foreach ($this->docDefaults as $k => $def) {
            $this->state['documentos'][$k] = $valor;
        }
    }

    public function resetForm()
    {
        $this->state = [];
        $this->ubicacion = null;
        $this->showEditModal = false;
    }

    public function mostrarMovimientos($id)
    {
        abort_unless(auth()->user()->can('movimientos.create'), 403);

        $this->dispatch('abrirModalMovimientos', $id);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($mov)
            ->withProperties([
                'detalle' => $this->detalle
            ])
            ->log('Cargó un movimiento');
    }
}

