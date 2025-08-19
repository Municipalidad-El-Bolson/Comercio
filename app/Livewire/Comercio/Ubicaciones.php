<?php

namespace App\Livewire\Comercio;

use App\Livewire\Admin\AdminComponent;
use App\Models\Rubro;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Models\UbicacionDocumento;


class Ubicaciones extends AdminComponent
{
    use WithPagination;

    public $searchTerm = '';
    public $state = [];
    public $ubicacion = null;
    public $showEditModal = false;
    public $rubros = [];

    /** Documentos booleanos soportados (clave => default) */
    // 1) Listas de claves
    protected array $docKeysGeneral = [
        'doc_libre_deuda_municipal',
        'doc_planeamiento_urbano',
        'doc_solicitud_habilitacion_pago',
        'doc_comprobante_uso_local',
        'doc_afip_constancia',
        'doc_recaudacion_rn',
        'doc_fotocopia_dni',
        'doc_comprobante_uso_inmueble',
        'doc_libre_deuda_tasas_inmueble',
        'doc_aptitud_tecnica_local',
        'doc_cocap_rhi',
        'doc_nota_carteleria_obras',
        'doc_libro_actas_100',
    ];

    protected array $docKeysJuridica = [
        'doc_acta_constitucion',
        'doc_contrato_societario',
        'doc_docs_representantes',
    ];

    // 2) Defaults (usa las mismas claves que tu formulario y tu tabla)
    protected array $docDefaults = [];

    public function mount()
    {
        $this->docDefaults = array_fill_keys(
            array_merge($this->docKeysGeneral, $this->docKeysJuridica),
            false
        );
        // Para el combo de Rubro en el modal
        $this->rubros = Rubro::select('id', 'rubro_madre', 'subrubro')->orderBy('rubro_madre')->orderBy('subrubro')->get();
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
        $rulesDocs = [
            'documentos.doc_libre_deuda_municipal'   => 'boolean',
            'documentos.doc_planeamiento_urbano'     => 'boolean',
            'documentos.doc_solicitud_habilitacion_pago' => 'boolean',
            'documentos.doc_comprobante_uso_local'   => 'boolean',
            'documentos.doc_fotocopia_dni'           => 'boolean',
            'documentos.doc_constancia_recaudacion'  => 'boolean',
            // Si en la UI usas la genérica, valida esa:
            'documentos.doc_afip_constancia'         => 'boolean',
            // Y si ya usas las dos, valida estas:
            'documentos.doc_afip_constancia_fisica'  => 'boolean',
            'documentos.doc_afip_constancia_juridica' => 'boolean',
            // Jurídicas:
            'documentos.doc_acta_constitucion'       => 'boolean',
            'documentos.doc_contrato_societario'     => 'boolean',
            'documentos.doc_docs_representantes'     => 'boolean',
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
        foreach (['razon_social', 'apellido', 'nombres', 'domicilio_responsable', 'nombre_comercial', 'domicilio_comercio'] as $campo) {
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

        $validated['fecha_alta'] = $this->state['fecha_alta'] ?? null;
        $validated['fecha_baja'] = $this->state['situacion'] === 'baja'
            ? ($this->state['fecha_baja'] ?? null)
            : null;

        // NO enviar campos legacy:
        unset($validated['dni'], $validated['direccion'], $validated['tipo']);

        // Documento checklist
        $documentos = $validated['documentos'] ?? [];
        unset($validated['documentos']);

        // Crear Ubicacion
        $ubic = Ubicacion::create($validated);

        if (array_key_exists('doc_afip_constancia', $documentos)) {
            if (($this->state['persona_tipo'] ?? 'fisica') === 'juridica') {
                $documentos['doc_afip_constancia_juridica'] = (bool)$documentos['doc_afip_constancia'];
            } else {
                $documentos['doc_afip_constancia_fisica'] = (bool)$documentos['doc_afip_constancia'];
            }
            unset($documentos['doc_afip_constancia']);
        }

        // Si tu UI manda 'doc_recaudacion_rn', mapea a la columna real:
        if (array_key_exists('doc_recaudacion_rn', $documentos)) {
            $documentos['doc_constancia_recaudacion'] = (bool)$documentos['doc_recaudacion_rn'];
            unset($documentos['doc_recaudacion_rn']);
        }

        // Elimina llaves que no existan en la tabla para evitar el 1054
        $permitidos = array_flip((new UbicacionDocumento)->getFillable());
        $documentos = array_intersect_key($documentos, $permitidos);

        // Defaults a false si no vienen
        foreach ($permitidos as $campo => $_) {
            $documentos[$campo] = (bool)($documentos[$campo] ?? false);
        }

        // Setear el FK
        $documentos['ubicacion_id'] = $ubic->id;

        // Crear checklist
        $ubic->documentos()->create($documentos);

        // Crear/guardar checklist de documentos (hasOne)
        $ubic->documentos()->create(array_merge($this->docDefaults, $documentos));

        // Reset UI
        $this->resetPage();
        $this->reset('state');
        $this->dispatch('hide-form', ['message' => 'Comercio creado correctamente.']);
    }

    /** Actualizar */
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
            'documentos' => 'array',
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

        // Separar documentos del resto
        $documentos = $validated['documentos'] ?? [];
        unset($validated['documentos']);

        // Guardar Ubicacion
        $this->ubicacion->update($validated);

        // Guardar/actualizar Documentos (solo claves válidas)
        $permitidos = array_intersect_key($documentos, $this->docDefaults);

        $this->ubicacion->documentos()
            ->updateOrCreate(
                ['ubicacion_id' => $this->ubicacion->id],
                $permitidos
            );

        $validated = Validator::make($this->state, $rules)->validate();

        // Formateos
        foreach (['razon_social', 'apellido', 'nombres', 'domicilio_responsable', 'nombre_comercial', 'domicilio_comercio'] as $campo) {
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

        $validated['fecha_alta'] = $this->state['fecha_alta'] ?? null;
        $validated['fecha_baja'] = $this->state['situacion'] === 'baja'
            ? ($this->state['fecha_baja'] ?? null)
            : null;

        // NO enviar campos legacy:
        unset($validated['dni'], $validated['direccion'], $validated['tipo']);

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
    }

    /** Botón "Presentó toda la documentación" / "Limpiar" */
    public function marcarTodosLosDocs(bool $valor = true): void
    {
        $docs = $this->state['documentos'] ?? [];

        // generales siempre
        foreach ($this->docKeysGeneral as $k) {
            $docs[$k] = $valor;
        }

        // jurídicas solo si corresponde
        if (($this->state['persona_tipo'] ?? 'fisica') === 'juridica') {
            foreach ($this->docKeysJuridica as $k) {
                $docs[$k] = $valor;
            }
        }

        // merge final (no se pierden claves existentes)
        $this->state['documentos'] = array_merge($this->docDefaults, $docs);
    }

    public function updatedStatePersonaTipo($tipo): void
    {
        if ($tipo === 'fisica') {
            $this->state['documentos'] = $this->state['documentos'] ?? [];
            foreach ($this->docKeysJuridica as $k) {
                $this->state['documentos'][$k] = false;
            }
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
        $this->dispatch('abrirModalMovimientos', $id);
    }
}
