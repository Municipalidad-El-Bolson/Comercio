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
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Carbon\Carbon;


class Ubicaciones extends AdminComponent
{
    use WithPagination;

    public $searchTerm = '';
    public $state = [
        'tipo_hab'   => 'prev',
        'documentos' => [],
    ];

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

    public ?int $ubicacionIdParaMapa = null;

    public function abrirMapa(int $id): void
    {
        $u = Ubicacion::with('rubro:id,subrubro')->findOrFail($id);

        $payload = [
            'id'        => $u->id,
            'razon'     => $u->razon_social,
            'dni_cuit'  => $u->dni_cuit,
            'persona'   => ucfirst($u->persona_tipo),
            'estado'    => ucfirst($u->estado),
            'situacion' => ucfirst($u->situacion),
            'domicilio' => $u->domicilio_comercio,
            'subrubro'  => optional($u->rubro)->subrubro,
            'lat'       => $u->lat,
            'lng'       => $u->lng,
        ];

        $this->dispatch('mostrar-modal-mapa', payload: $payload);
    }

    public function mount()
    {
        $this->docDefaults = array_fill_keys(
            array_merge($this->docKeysGeneral, $this->docKeysJuridica),
            false
        );
        $this->state['documentos'] = $this->state['documentos'] ?? [];
        $this->megas = Rubro::query()
            ->select('mega_rubro')
            ->distinct()
            ->orderBy('mega_rubro')
            ->pluck('mega_rubro')
            ->toArray();

        $this->madres = [];
        $this->subs   = [];
    }

    public function updated($name, $value)
    {
        if (!isset($this->state['documentos']) || !is_array($this->state['documentos'])) {
            $this->state['documentos'] = [];
        }
        if ($name === 'state.persona_tipo' || str_starts_with($name, 'state.documentos.')) {
        }
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
            'megas'  => $this->megas,
            'madres' => $this->madres,
            'subs'   => $this->subs,
        ])->layout('admin.layouts.app');
    }

    private function recomputarVtoEnState(): void
    {
        $estado   = $this->state['estado']    ?? null;
        $tipoHab  = $this->state['tipo_hab']  ?? 'prev';       // 'definitiva' | 'prev'
        $fechaAlta= $this->state['fecha_alta']?? null;         // 'YYYY-MM-DD'

        if (in_array($estado, ['vigente','irregular']) && $fechaAlta) {
            $alta = Carbon::parse($fechaAlta);
            $vto  = $tipoHab === 'definitiva'
                ? $alta->copy()->addYearNoOverflow()
                : $alta->copy()->addMonthsNoOverflow(6);
            // formatear para <input type="date">
            $this->state['fecha_vto'] = $vto->format('Y-m-d');
        } else {
            $this->state['fecha_vto'] = null;
        }
    }

    // hooks de Livewire: se disparan cuando cambia cada campo
    public function updatedStateFechaAlta($value): void   { $this->recomputarVtoEnState(); }
    public function updatedStateTipoHab($value): void     { $this->recomputarVtoEnState(); }
    public function updatedStateEstado($value): void      { $this->recomputarVtoEnState(); }

    /** Botón "Nuevo Comercio" */
    public function nuevoComercio()
    {
        $this->reset('state', 'ubicacion', 'selectedMega', 'selectedMadre', 'madres', 'subs');

        $this->state = [
            'persona_tipo'  => 'fisica',
            'estado'        => null,
            'tipo_hab'      => 'prev',
            'fecha_alta'    => null,
            'fecha_baja'    => null,
            'fecha_vto'     => null,
            'rubro_id'      => null,
            'dni_cuit'      => '',
            'apellido'      => '',
            'nombres'       => '',
            'razon_social'  => '',
            'nombre_comercial' => '',
            'domicilio_responsable' => '',
            'domicilio_comercio'    => '',
            'correo'        => '',
            'telefono'      => '',
            'nomenclatura'  => '',
            'monto_pagar'   => null,
            'observaciones' => '',
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

        foreach (['fecha_alta','fecha_baja','fecha_vto'] as $f) {
            if (!empty($this->ubicacion->{$f})) {
                $this->state[$f] = $this->ubicacion->{$f}->format('Y-m-d');
            } else {
                $this->state[$f] = null;
            }
        }

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

        $docs = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $this->state['documentos'] = array_merge($this->docDefaults, array_intersect_key($docs, $this->docDefaults));

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


    /** ===== Helpers de reglas ===== */

    private function reglasComunes(bool $isUpdate = false): array
    {
        // Unique para dni_cuit
        $uniqueDniCuit = Rule::unique('ubicaciones', 'dni_cuit'); // ajustar si la tabla es otra
        if ($isUpdate && $this->ubicacion?->id) {
            $uniqueDniCuit = $uniqueDniCuit->ignore($this->ubicacion->id);
        }

        $rules = [
            'state.persona_tipo'          => ['required', Rule::in(['fisica','juridica'])],
            'state.dni_cuit'              => ['bail','required', 'string', $uniqueDniCuit, 'regex:/^\d{7,8}$|^\d{11}$/', function ($attr, $value, $fail) {
                if (strlen(preg_replace('/\D/','', $value)) === 11 && !$this->isValidCuit($value)) {
                    $fail('El CUIT no es válido.');
                }
            }],
            'state.rubro_id'              => ['required','exists:rubros,id'],

            'state.apellido'              => ['nullable','string','min:2','max:60'],
            'state.nombres'               => ['nullable','string','min:2','max:80'],
            'state.razon_social'          => ['nullable','string','min:2','max:120'],
            'state.nombre_comercial'      => ['nullable','string','min:2','max:120'],

            'state.domicilio_responsable' => ['required','string','min:3','max:160'],
            'state.domicilio_comercio'    => ['nullable','string','min:3','max:160','required_without:state.nomenclatura'],
            'state.correo'                => ['nullable','email:rfc,dns','max:120'],
            'state.telefono'              => ['nullable','regex:/^[\d\s()+\-]{6,20}$/'],
            'state.nomenclatura'          => ['nullable','string','max:80','required_without:state.domicilio_comercio'],
            'state.monto_pagar'           => ['nullable','numeric','min:0','regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'state.observaciones'         => ['nullable','string','max:500'],

            'state.estado'                => ['required', Rule::in(['entramite','vigente','irregular','baja'])],
            'state.tipo_hab'              => ['required', Rule::in(['definitiva','prev'])],
            'state.fecha_alta'            => ['nullable','date'],
            'state.fecha_baja'            => ['nullable','date'],
            'state.fecha_vto'             => ['nullable','date'],

            'state.documentos'            => ['array'],
        ];

        // Documentos booleanos
        foreach (array_keys($this->docDefaults) as $key) {
            $rules["state.documentos.$key"] = ['boolean'];
        }

        // Condicionales persona
        if (($this->state['persona_tipo'] ?? 'fisica') === 'fisica') {
            $rules['state.apellido'] = ['required','string','min:2','max:60'];
            $rules['state.nombres']  = ['required','string','min:2','max:80'];
        } else {
            $rules['state.razon_social'] = ['required','string','min:2','max:120'];
        }

        return $rules;
    }

    private function mensajes(): array
    {
        return [
            'state.apellido.min'           => 'El apellido debe tener al menos :min caracteres.',
            'state.nombres.min'            => 'Los nombres deben tener al menos :min caracteres.',
            'state.nombre_comercial.min'   => 'El nombre comercial debe tener al menos :min caracteres.',

            'state.persona_tipo.required' => 'Seleccioná el tipo de persona.',
            'state.persona_tipo.in'       => 'El tipo debe ser física o jurídica.',
            'state.dni_cuit.required'     => 'Ingresá DNI o CUIT.',
            'state.dni_cuit.regex'        => 'Usá DNI (7–8 dígitos) o CUIT (11 dígitos).',
            'state.dni_cuit.unique'       => 'Ya existe un registro con ese DNI/CUIT.',
            'state.rubro_id.required'     => 'Seleccioná el subrubro.',
            'state.rubro_id.exists'       => 'El subrubro seleccionado no es válido.',

            'state.apellido.required'     => 'El apellido es obligatorio.',
            'state.nombres.required'      => 'Los nombres son obligatorios.',
            'state.razon_social.required' => 'La razón social es obligatoria.',

            'state.domicilio_responsable.required' => 'Ingresá el domicilio del responsable.',
            'state.domicilio_comercio.required'    => 'Ingresá el domicilio del comercio.',
            'state.correo.email'                   => 'Ingresá un correo válido.',
            'state.telefono.regex'                 => 'Formato de teléfono inválido.',
            'state.monto_pagar.numeric'            => 'El monto debe ser numérico.',
            'state.monto_pagar.regex'              => 'Usá hasta 2 decimales (ej: 123.45).',
            'state.estado.required'                => 'Seleccioná el estado.',
        ];
    }

    
    private function atributos(): array
    {
        return [
            'state.persona_tipo'          => 'tipo de persona',
            'state.dni_cuit'              => 'DNI/CUIT',
            'state.apellido'              => 'apellido',
            'state.nombres'               => 'nombres',
            'state.razon_social'          => 'razón social',
            'state.nombre_comercial'      => 'nombre comercial',
            'state.domicilio_responsable' => 'domicilio del responsable',
            'state.domicilio_comercio'    => 'domicilio del comercio',
            'state.correo'                => 'correo electrónico',
            'state.telefono'              => 'teléfono',
            'state.nomenclatura'          => 'nomenclatura',
            'state.monto_pagar'           => 'monto a pagar',
            'state.estado'                => 'estado',
            'state.fecha_alta'            => 'fecha de alta',
            'state.fecha_baja'            => 'fecha de baja',
            'state.fecha_vto'             => 'fecha de vencimiento',
        ];
    }

    /**Crear*/
    public function createCliente()
    {
        $this->aplicarFlagsEstadoEnState();

        $validated = $this->validate(
            array_merge($this->reglasComunes(false), $this->reglasFechasPorEstado(true)),
            $this->mensajes(),
            $this->atributos()
        );

        $data = $validated['state'];


        // Formateos
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $c) {
            if (!empty($data[$c])) $data[$c] = Str::title($data[$c]);
        }

        // Identidad coherente
        $esFisica = ($data['persona_tipo'] ?? 'fisica') === 'fisica';
        if ($esFisica) {
            $data['razon_social'] = $data['razon_social'] ?? null;
        } else {
            $data['apellido'] = $data['apellido'] ?? null;
            $data['nombres']  = $data['nombres']  ?? null;
        }

        // Documentos
        $documentos = $data['documentos'] ?? [];
        unset($data['documentos']);

        // Mapeos opcionales de documentos
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

        $enricher = app(\App\Services\UbicacionGeoEnricher::class);
        $data = $enricher->enrich($data);
        $ubic = Ubicacion::create($data);

        // Guardar checklist (hasOne)
        $permitidos = array_flip((new UbicacionDocumento)->getFillable());
        $documentos = array_intersect_key($documentos, $permitidos);
        foreach ($permitidos as $campo => $_) {
            $documentos[$campo] = (bool)($documentos[$campo] ?? false);
        }

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
        $this->aplicarFlagsEstadoEnState();

        $validated = $this->validate(
            array_merge($this->reglasComunes(true), $this->reglasFechasPorEstado(false)),
            $this->mensajes(),
            $this->atributos()
        );

        $data = $validated['state'];
        

        // Formateos
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $c) {
            if (!empty($data[$c])) $data[$c] = Str::title($data[$c]);
        }

        // Identidad coherente
        $esFisica = ($data['persona_tipo'] ?? 'fisica') === 'fisica';
        if ($esFisica) {
            $data['razon_social'] = $data['razon_social'] ?? null;
        } else {
            $data['apellido'] = $data['apellido'] ?? null;
            $data['nombres']  = $data['nombres']  ?? null;
        }

        // Documentos
        $documentos = $data['documentos'] ?? [];
        unset($data['documentos']);

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

        // Filtrar a columnas válidas en documentos
        $permitidos = array_flip((new UbicacionDocumento)->getFillable());
        $documentos = array_intersect_key($documentos, $permitidos);

        // Guardar Ubicación (el modelo normaliza fechas por estado)
        $this->ubicacion->update($data);

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

    /*Flags y reglas por estado*/

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
            'state.fecha_alta' => 'nullable|date',
            'state.fecha_baja' => 'nullable|date',
            'state.fecha_vto'  => 'nullable|date',
        ];

        switch ($estado) {
            case 'entramite':
                break;

            case 'vigente':
                if ($esCreate) {
                    $reglas['state.fecha_alta'] = 'required|date';
                } else {
                    $prev = $this->ubicacion?->getOriginal('estado') ?? null;
                    if ($prev !== 'entramite') {
                        $reglas['state.fecha_alta'] = 'required|date';
                    }
                }
                // vto lo calcula el modelo si hay alta
                break;

            case 'irregular':
                $reglas['state.fecha_alta'] = 'required|date';
                break;

            case 'baja':

                $reglas['state.fecha_baja'] = 'required|date'
                    . (empty($this->state['fecha_alta']) && empty($this->ubicacion?->fecha_alta)
                        ? '' 
                        : '|after_or_equal:state.fecha_alta')
                    . '|before_or_equal:today';

                // si no hay alta ni antes ni ahora, pedila:
                if (empty($this->state['fecha_alta']) && empty($this->ubicacion?->fecha_alta)) {
                    $reglas['state.fecha_alta'] = 'required|date|before_or_equal:today';
                }

                // vto no aplica
                $reglas['state.fecha_vto']  = 'nullable';
                break;
        }

        return $reglas;
    }

    /*CUIT*/
    private function isValidCuit(string $cuit): bool
    {
        $cuit = preg_replace('/\D/','', $cuit);
        if (!preg_match('/^\d{11}$/', $cuit)) return false;
        $digits = array_map('intval', str_split($cuit));
        $weights = [5,4,3,2,7,6,5,4,3,2];
        $sum = 0;
        for ($i=0; $i<10; $i++) $sum += $digits[$i] * $weights[$i];
        $mod = $sum % 11;
        $check = $mod === 0 ? 0 : ($mod === 1 ? 9 : 11 - $mod);
        return $check === $digits[10];
    }
    
}
