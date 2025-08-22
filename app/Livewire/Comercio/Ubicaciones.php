<?php

namespace App\Livewire\Comercio;

use App\Livewire\Admin\AdminComponent;
use App\Models\Rubro;
use App\Models\Ubicacion;
use App\Models\UbicacionDocumento;
use App\Models\ComercioEstado;
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

    public array $megas   = [];
    public array $madres  = [];
    public array $subs    = [];
    public string $selectedMega  = '';
    public string $selectedMadre = '';

    /** Documentos booleanos soportados (clave => default) */
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

    protected array $docDefaults = [];

    public function mount()
    {
        $this->docDefaults = array_fill_keys(
            array_merge($this->docKeysGeneral, $this->docKeysJuridica),
            false
        );

        // cargar solo los megas (distinct)
        $this->megas = Rubro::query()
            ->select('mega_rubro')
            ->distinct()
            ->orderBy('mega_rubro')
            ->pluck('mega_rubro')
            ->toArray();

        // estado inicial
        $this->madres = [];
        $this->subs   = [];
        
    }

    public function updatingSearchTerm() { $this->resetPage(); }

    public function render()
    {
        $ubicaciones = Ubicacion::with(['rubro','estadoModel'])
            ->where(function($q){
                $t = '%'.$this->searchTerm.'%';
                $q->where('razon_social','like',$t)
                  ->orWhere('apellido','like',$t)
                  ->orWhere('nombres','like',$t);
            })
            ->orderBy('razon_social')->paginate(10);

        return view('livewire.comercio.ubicaciones', [
            'ubicaciones' => $ubicaciones,
            // pasamos las listas simples; no $rubros gigante
            'megas'  => $this->megas,
            'madres' => $this->madres,
            'subs'   => $this->subs,
        ])->layout('admin.layouts.app');
    }
    /** Botón "Nuevo Comercio" */
    public function nuevoComercio()
    {
        $this->reset('state', 'ubicacion', 'selectedMega', 'selectedMadre', 'madres', 'subs');

        $this->state = [
            'persona_tipo'  => 'fisica',
            'estado'        => 'entramite',
            'fecha_alta'    => null,
            'fecha_baja'    => null,
            'fecha_vto'     => null,
            'rubro_id'      => null,
            'documentos'    => $this->docDefaults,
        ];

        $this->showEditModal = false;
        $this->dispatch('show-form');
    }

    public function editaComercio(Ubicacion $ubicacion)
    {
        $this->showEditModal = true;
        $this->ubicacion = $ubicacion->loadMissing('documentos', 'rubro');

        $this->state = $this->ubicacion->toArray();
        $docs = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $this->state['documentos'] = array_merge($this->docDefaults, array_intersect_key($docs, $this->docDefaults));

        // Precarga: si hay rubro_id => cargar selectedMega/Madre/Subs coherentes
        if ($this->ubicacion->rubro_id) {
            $r = Rubro::find($this->ubicacion->rubro_id);
            if ($r) {
                $this->selectedMega  = $r->mega_rubro ?? '';
                $this->madres = Rubro::where('mega_rubro', $this->selectedMega)
                    ->select('rubro_madre')->distinct()->orderBy('rubro_madre')->pluck('rubro_madre')->toArray();

                $this->selectedMadre = $r->rubro_madre ?? '';
                $this->subs = Rubro::where('mega_rubro', $this->selectedMega)
                    ->where('rubro_madre', $this->selectedMadre)
                    ->orderBy('subrubro')
                    ->get(['id','subrubro'])
                    ->map(fn($x)=>['id'=>$x->id,'sub'=>$x->subrubro])
                    ->toArray();
            }
        }

        $this->dispatch('show-form');
    }

    public function updatedSelectedMega($value)
    {
        $this->selectedMadre = '';
        $this->state['rubro_id'] = null;

        $this->madres = $value
            ? Rubro::where('mega_rubro', $value)
                ->select('rubro_madre')->distinct()->orderBy('rubro_madre')
                ->pluck('rubro_madre')->toArray()
            : [];

        $this->subs = [];
    }
    

    /** Cuando cambia el Rubro Madre */
    public function updatedSelectedMadre($value)
    {
        $this->state['rubro_id'] = null;

        $this->subs = ($this->selectedMega && $value)
            ? Rubro::where('mega_rubro', $this->selectedMega)
                ->where('rubro_madre', $value)
                ->orderBy('subrubro')
                ->get(['id','subrubro'])
                ->map(fn($x)=>['id'=>$x->id,'sub'=>$x->subrubro])
                ->toArray()
            : [];
    }

    public function onMegaChange() { $this->updatedSelectedMega($this->selectedMega); }
    public function onMadreChange() { $this->updatedSelectedMadre($this->selectedMadre); }
    
    /** Crear */
    public function createCliente()
    {
        $rulesBase = [
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

            'estado'                => 'required|in:entramite,vigente,baja',
            'fecha_alta'            => 'nullable|date',
            'fecha_baja'            => 'nullable|date',
            'fecha_vto'             => 'nullable|date',
            'documentos'            => 'array',
        ];

        $rules = array_merge($rulesBase, $this->reglasFechasPorEstado(true));

        // Reglas booleanas para todos los docs
        foreach (array_keys($this->docDefaults) as $key) {
            $rules["documentos.$key"] = 'boolean';
        }

        // Reglas identidad
        if (($this->state['persona_tipo'] ?? 'fisica') === 'fisica') {
            $rules['apellido'] = 'required|string';
            $rules['nombres']  = 'required|string';
        } else {
            $rules['razon_social'] = 'required|string';
        }

        // Ajustar por flags del estado (oculta/ignora fechas que no aplican)
        $this->aplicarFlagsEstadoEnState();

        $validated = Validator::make($this->state, $rules)->validate();

        // Formateos de texto
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $c) {
            if (!empty($validated[$c])) $validated[$c] = Str::title($validated[$c]);
        }

        // Identidad coherente
        $esFisica = ($validated['persona_tipo'] ?? 'fisica') === 'fisica';
        if ($esFisica) {
            $validated['razon_social'] = $validated['razon_social'] ?? null;
        } else {
            $validated['apellido'] = $validated['apellido'] ?? null;
            $validated['nombres']  = $validated['nombres']  ?? null;
        }

        // Campos legacy fuera
        unset($validated['dni'], $validated['direccion'], $validated['tipo']);

        // Documentos
        $documentos = $validated['documentos'] ?? [];
        unset($validated['documentos']);

        // Mapeos de nombres que usás en el form
        if (array_key_exists('doc_afip_constancia', $documentos)) {
            if (($this->state['persona_tipo'] ?? 'fisica') === 'juridica') {
                $documentos['doc_afip_constancia_juridica'] = (bool)$documentos['doc_afip_constancia'];
            } else {
                $documentos['doc_afip_constancia_fisica'] = (bool)$documentos['doc_afip_constancia'];
            }
            unset($documentos['doc_afip_constancia']);
        }
        if (array_key_exists('doc_recaudacion_rn', $documentos)) {
            $documentos['doc_constancia_recaudacion'] = (bool)$documentos['doc_recaudacion_rn'];
            unset($documentos['doc_recaudacion_rn']);
        }

        // Filtrar a columnas válidas
        $permitidos = array_flip((new UbicacionDocumento)->getFillable());
        $documentos = array_intersect_key($documentos, $permitidos);
        foreach ($permitidos as $campo => $_) {
            $documentos[$campo] = (bool)($documentos[$campo] ?? false);
        }

        // Crear Ubicación (el modelo setea fechas según estado en saving())
        $ubic = Ubicacion::create($validated);

        // Guardar checklist (hasOne)
        $ubic->documentos()->updateOrCreate(
            ['ubicacion_id' => $ubic->id],
            array_merge($this->docDefaults, $documentos, ['ubicacion_id' => $ubic->id])
        );

        // Reset UI
        $this->resetPage();
        $this->reset('state');
        $this->dispatch('hide-form', ['message' => 'Comercio creado correctamente.']);
    }

    /** Actualizar */
    public function updateComercio()
    {
        $rulesBase  = [
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

            'estado'                => 'required|in:entramite,vigente,baja',
            'fecha_alta'            => 'nullable|date',
            'fecha_baja'            => 'nullable|date',
            'fecha_vto'             => 'nullable|date',
            'documentos'            => 'array',
        ];
        
        $rules = array_merge($rulesBase, $this->reglasFechasPorEstado(false));


        foreach (array_keys($this->docDefaults) as $key) {
            $rules["documentos.$key"] = 'boolean';
        }

        if (($this->state['persona_tipo'] ?? 'fisica') === 'fisica') {
            $rules['apellido'] = 'required|string';
            $rules['nombres']  = 'required|string';
        } else {
            $rules['razon_social'] = 'required|string';
        }

        // Flags por estado
        $this->aplicarFlagsEstadoEnState();

        $validated = Validator::make($this->state, $rules)->validate();

        // Formateos
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $c) {
            if (!empty($validated[$c])) $validated[$c] = Str::title($validated[$c]);
        }

        // Identidad coherente
        $esFisica = ($validated['persona_tipo'] ?? 'fisica') === 'fisica';
        if ($esFisica) {
            $validated['razon_social'] = $validated['razon_social'] ?? null;
        } else {
            $validated['apellido'] = $validated['apellido'] ?? null;
            $validated['nombres']  = $validated['nombres']  ?? null;
        }
        
        unset($validated['dni'], $validated['direccion'], $validated['tipo']);

        // Documentos
        $documentos = $validated['documentos'] ?? [];
        unset($validated['documentos']);

        if (array_key_exists('doc_afip_constancia', $documentos)) {
            if (($this->state['persona_tipo'] ?? 'fisica') === 'juridica') {
                $documentos['doc_afip_constancia_juridica'] = (bool)$documentos['doc_afip_constancia'];
            } else {
                $documentos['doc_afip_constancia_fisica'] = (bool)$documentos['doc_afip_constancia'];
            }
            unset($documentos['doc_afip_constancia']);
        }
        if (array_key_exists('doc_recaudacion_rn', $documentos)) {
            $documentos['doc_constancia_recaudacion'] = (bool)$documentos['doc_recaudacion_rn'];
            unset($documentos['doc_recaudacion_rn']);
        }

        $permitidos = array_flip((new UbicacionDocumento)->getFillable());
        $documentos = array_intersect_key($documentos, $permitidos);

        // Guardar Ubicación (el modelo normaliza fechas por estado)
        $this->ubicacion->update($validated);

        // Guardar checklist (crea si no existe)
        $this->ubicacion->documentos()->updateOrCreate(
            ['ubicacion_id' => $this->ubicacion->id],
            array_merge($this->docDefaults, $documentos, ['ubicacion_id' => $this->ubicacion->id])
        );

        $this->resetPage();
        $this->dispatch('hide-form', ['message' => 'Registro actualizado correctamente']);
    }

    /** Botón "Presentó toda la documentación" / "Limpiar" */
    public function marcarTodosLosDocs(bool $valor = true): void
    {
        $docs = $this->state['documentos'] ?? [];

        foreach ($this->docKeysGeneral as $k) {
            $docs[$k] = $valor;
        }
        if (($this->state['persona_tipo'] ?? 'fisica') === 'juridica') {
            foreach ($this->docKeysJuridica as $k) {
                $docs[$k] = $valor;
            }
        }

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

    /**
     * Aplica los flags del estado seleccionado sobre $this->state,
     * para que el form no exija/guarde fechas que no correspondan.
     */
    private function aplicarFlagsEstadoEnState(): void
    {
        $codigo = $this->state['estado'] ?? 'entramite';
        $estado = ComercioEstado::find($codigo);

        if (!$estado) return;

        if (!$estado->aplica_fecha_alta) $this->state['fecha_alta'] = null;
        if (!$estado->aplica_fecha_baja) $this->state['fecha_baja'] = null;
        if (!$estado->aplica_fecha_vto)  $this->state['fecha_vto']  = null;
    }

    private function reglasFechasPorEstado(bool $esCreate): array
    {
        $estado = $this->state['estado'] ?? 'entramite';
        $reglas = [
            'fecha_alta' => 'nullable|date',
            'fecha_baja' => 'nullable|date',
            'fecha_vto'  => 'nullable|date',
        ];

        switch ($estado) {
            case 'entramite':
                // nada requerido
                break;

            case 'vigente':
                if ($esCreate) {
                    // alta inicial de un comercio ya vigente -> requiere fecha_alta
                    $reglas['fecha_alta'] = 'required|date';
                } else {
                    // si venía de 'entramite', se podrá dejar vacía (el modelo la pondrá HOY)
                    $prev = $this->ubicacion?->getOriginal('estado') ?? null;
                    if ($prev !== 'entramite') {
                        $reglas['fecha_alta'] = 'required|date';
                    }
                }
                break;

            case 'irregular':
                $reglas['fecha_alta'] = 'required|date';
                break;

            case 'baja':
                // Debe tener alta (si no venía con una)
                if (empty($this->state['fecha_alta']) && empty($this->ubicacion?->fecha_alta)) {
                    $reglas['fecha_alta'] = 'required|date';
                }
                // fecha_baja NO se pide: la pone el modelo como HOY
                $reglas['fecha_baja'] = 'nullable';
                $reglas['fecha_vto']  = 'nullable';
                break;
        }
        return $reglas;
    }

}
