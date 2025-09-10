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
use Illuminate\Support\Facades\Gate;


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

    public array $madresOptions = [];
    public array $subsOptions   = [];

    public array $megas   = [];
    public array $madres  = [];
    public array $subs    = [];
    public string $selectedMega  = '';
    public string $selectedMadre = '';
    public string $formKey = '';

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
        abort_unless(Gate::allows('manage-ubicaciones'), 403);
        
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
        $this->formKey = (string) \Illuminate\Support\Str::uuid();
    }

    public function updated($name, $value)
    {
        if (!isset($this->state['documentos']) || !is_array($this->state['documentos'])) {
            $this->state['documentos'] = [];
        }
        if ($name === 'state.persona_tipo' || str_starts_with($name, 'state.documentos.')) {
        }
        if (preg_match('/^state\.rubros\.(\d+)\.mega$/', $name, $m)) {
            $i = (int)$m[1];
            $mega = (string)($value ?? '');

            // Reset dependientes
            $this->state['rubros'][$i]['madre']  = '';
            $this->state['rubros'][$i]['sub_id'] = null;

            // Recalcular madres
            $this->madresOptions[$i] = $mega !== ''
                ? \App\Models\Rubro::where('mega_rubro', $mega)
                    ->select('rubro_madre')->distinct()->orderBy('rubro_madre')
                    ->pluck('rubro_madre')->toArray()
                : [];
            // Vaciar subs
            $this->subsOptions[$i] = [];
        }

        if (preg_match('/^state\.rubros\.(\d+)\.madre$/', $name, $m)) {
            $i = (int)$m[1];
            $mega  = (string)($this->state['rubros'][$i]['mega']  ?? '');
            $madre = (string)($this->state['rubros'][$i]['madre'] ?? '');

            // Reset sub
            $this->state['rubros'][$i]['sub_id'] = null;

            // Recalcular subs
            $this->subsOptions[$i] = ($mega !== '' && $madre !== '')
                ? \App\Models\Rubro::where('mega_rubro', $mega)
                    ->where('rubro_madre', $madre)
                    ->orderBy('subrubro')
                    ->get(['id','subrubro'])
                    ->map(fn($x)=>['id'=>$x->id,'sub'=>$x->subrubro])
                    ->toArray()
                : [];
        }
    }


    public function updatingSearchTerm() { $this->resetPage(); }

    public function render()
    {
        $t = '%'.$this->searchTerm.'%';

        $ubicaciones = Ubicacion::with(['rubro','estadoModel'])
            ->where('nombre_comercial','like',$t)
            ->orderBy('nombre_comercial')
            ->paginate(10);

        return view('livewire.comercio.ubicaciones', [
            'ubicaciones' => $ubicaciones,
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
            'telefonos'      => [''],           // ← múltiples teléfonos
            'rubros'         => [               // ← múltiples rubros (repeater)
                ['mega'=>'', 'madre'=>'', 'sub_id'=>null],
            ],
            'disposiciones'  => [['numero'=>'', 'fecha'=>null]], // opcional fecha
            'habilitaciones' => [['numero'=>'', 'fecha'=>null]],
            'documentos'    => $this->docDefaults,
        ];

        $this->formKey = (string) \Illuminate\Support\Str::uuid();
        $this->showEditModal = false;
        $this->dispatch('show-form');
    }

    public function editaComercio(Ubicacion $ubicacion)
    {
        $this->showEditModal = true;

        // Cargar relaciones nuevas para el form
        $this->ubicacion = $ubicacion->loadMissing([
            'rubro',            // compat legado
            'rubros',           // many-to-many
            'telefonos',        // 1-N
            'disposiciones',    // 1-N
            'habilitaciones',   // 1-N
        ]);

        // Base de state desde el modelo
        $this->state = $this->ubicacion->toArray();

        // Fechas en formato Y-m-d para inputs
        foreach (['fecha_alta','fecha_baja','fecha_vto'] as $f) {
            $this->state[$f] = !empty($this->ubicacion->{$f})
                ? $this->ubicacion->{$f}->format('Y-m-d')
                : null;
        }

        // Normalizaciones de estado/situación (compat)
        $this->state['estado']    = $this->normalizarEstado($this->state['estado'] ?? null);
        $this->state['situacion'] = $this->ubicacion->situacion ?? ($this->state['situacion'] ?? null);

        // =======================
        // RUBROS (MÚLTIPLES)
        // =======================
        // Mapeamos los rubros del pivot a filas mega/madre/sub_id para el repeater
        $rubrosPivot = $this->ubicacion->rubros
            ->sortBy(fn($r) => $r->pivot->orden ?? 9999)
            ->values();

        $this->state['rubros'] = $rubrosPivot->map(function($r){
            return [
                'mega'   => $r->mega_rubro,
                'madre'  => $r->rubro_madre,
                'sub_id' => $r->id,
            ];
        })->all();

        $this->buildRubrosOptionsFromState();

        // Compatibilidad con selects "legados" (un solo rubro):
        if ($this->ubicacion->rubro_id) {
            $r = Rubro::find($this->ubicacion->rubro_id);
            if ($r) {
                $this->selectedMega  = $r->mega_rubro ?? '';
                $this->madres        = Rubro::where('mega_rubro', $this->selectedMega)
                                        ->select('rubro_madre')->distinct()->orderBy('rubro_madre')->pluck('rubro_madre')->toArray();

                $this->selectedMadre = $r->rubro_madre ?? '';
                $this->subs          = Rubro::where('mega_rubro', $this->selectedMega)
                                        ->where('rubro_madre', $this->selectedMadre)
                                        ->orderBy('subrubro')
                                        ->get(['id','subrubro'])
                                        ->map(fn($x)=>['id'=>$x->id,'sub'=>$x->subrubro])
                                        ->toArray();

                // ¡SIEMPRE después de cargar $subs!
                $this->state['rubro_id'] = (int) $this->ubicacion->rubro_id;
            }
        } else {
            // Si no hay rubro_id legado y tampoco rubros en el pivot, dejamos una fila vacía para el repeater
            if (empty($this->state['rubros'])) {
                $this->state['rubros'] = [['mega'=>'','madre'=>'','sub_id'=>null]];
            }
            // Limpieza de selects legados
            $this->selectedMega = '';
            $this->selectedMadre = '';
            $this->madres = [];
            $this->subs = [];
            $this->state['rubro_id'] = null;
        }

        // =======================
        // TELÉFONOS (MÚLTIPLES)
        // =======================
        $tels = $this->ubicacion->telefonos->pluck('telefono')->filter()->values()->all();
        $this->state['telefonos'] = !empty($tels) ? $tels : [''];

        // =======================
        // DISPOSICIONES (MÚLTIPLES)
        // =======================
        $this->state['disposiciones'] = $this->ubicacion->disposiciones->map(function($d){
            return [
                'numero' => (string)$d->numero,
                'fecha'  => $d->fecha ? $d->fecha->format('Y-m-d') : null,
            ];
        })->values()->all();
        if (empty($this->state['disposiciones'])) {
            $this->state['disposiciones'] = [['numero'=>'','fecha'=>null]];
        }

        // =======================
        // HABILITACIONES (MÚLTIPLES)
        // =======================
        $this->state['habilitaciones'] = $this->ubicacion->habilitaciones->map(function($h){
            return [
                'numero' => (string)$h->numero,
                'fecha'  => $h->fecha ? $h->fecha->format('Y-m-d') : null,
            ];
        })->values()->all();
        if (empty($this->state['habilitaciones'])) {
            $this->state['habilitaciones'] = [['numero'=>'','fecha'=>null]];
        }

        // =======================
        // DOCUMENTOS (checklist)
        // =======================
        $docs = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $this->state['documentos'] = array_merge($this->docDefaults, array_intersect_key($docs, $this->docDefaults));

        // Forzar nueva key para Livewire (evita residuos de DOM)
        $this->formKey = (string) \Illuminate\Support\Str::uuid();

        // Mostrar modal
        $this->dispatch('show-form');
    }


    public function rehidratarRubros(): void
    {
        $rid = (int)($this->state['rubro_id'] ?? 0);
        if ($rid > 0) {
            $r = Rubro::find($rid);
            if ($r) {
                $this->selectedMega  = $r->mega_rubro ?? '';
                $this->madres        = Rubro::where('mega_rubro', $this->selectedMega)
                                    ->select('rubro_madre')->distinct()->orderBy('rubro_madre')->pluck('rubro_madre')->toArray();

                $this->selectedMadre = $r->rubro_madre ?? '';
                $this->subs          = Rubro::where('mega_rubro', $this->selectedMega)
                                    ->where('rubro_madre', $this->selectedMadre)
                                    ->orderBy('subrubro')
                                    ->get(['id','subrubro'])
                                    ->map(fn($x)=>['id'=>$x->id,'sub'=>$x->subrubro])
                                    ->toArray();
            }
        }
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

        $rules = [
            'state.persona_tipo'          => ['required', Rule::in(['fisica','juridica'])],
            'state.dni_cuit' => [
                'bail','required', 'string',
                'regex:/^\d{7,8}$|^\d{2}-\d{7,8}-\d{1}$|^\d{11}$/',
                function ($attr, $value, $fail) {
                    if (strlen(preg_replace('/\D/','', $value)) === 11 && !$this->isValidCuit($value)) {
                        $fail('El CUIT no es válido.');
                    }
                }
            ],

            'state.rubro_id'              => ['required','exists:rubros,id'],

            'state.apellido'              => ['nullable','string','min:2','max:60'],
            'state.nombres'               => ['nullable','string','min:2','max:80'],
            'state.razon_social'          => ['nullable','string','min:2','max:120'],
            'state.nombre_comercial'      => ['nullable','string','min:2','max:120'],

            'state.domicilio_responsable' => ['nullable','string','min:3','max:160'],
            'state.domicilio_comercio'    => ['nullable','string','min:3','max:160'],
            'state.correo'                => ['nullable','email:rfc,dns','max:120'],
            'state.telefono'              => ['nullable','regex:/^[\d\s()+\-]{6,20}$/'],
            'state.nomenclatura'          => ['nullable','string','max:80'],
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
            'state.rubro_id.required'     => 'Seleccioná el subrubro.',
            'state.rubro_id.exists'       => 'El subrubro seleccionado no es válido.',

            'state.apellido.required'     => 'El apellido es obligatorio.',
            'state.nombres.required'      => 'Los nombres son obligatorios.',
            'state.razon_social.required' => 'La razón social es obligatoria.',

            'state.domicilio_responsable.required' => 'Ingresá el domicilio del responsable.',
            'state.domicilio_comercio'    => 'Ingresá el domicilio del comercio.',
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
    public function addTelefono(): void
    {
        $tels = $this->state['telefonos'] ?? [];
        if (!is_array($tels)) $tels = [];
        $tels[] = '';
        $this->state['telefonos'] = array_values($tels);
    }

    public function removeTelefono(int $index): void
    {
        $tels = $this->state['telefonos'] ?? [];
        if (!is_array($tels) || count($tels) <= 1) {
            // Siempre dejar al menos una fila
            return;
        }
        unset($tels[$index]);
        $this->state['telefonos'] = array_values($tels);
    }

    public function addRubroRow(): void
    {
        $rows = $this->state['rubros'] ?? [];
        if (!is_array($rows)) $rows = [];
        $rows[] = ['mega' => '', 'madre' => '', 'sub_id' => null];
        $this->state['rubros'] = array_values($rows);

        $i = count($this->state['rubros']) - 1;
        $this->madresOptions[$i] = [];
        $this->subsOptions[$i]   = [];
    }

    public function removeRubroRow(int $index): void
    {
        $rows = $this->state['rubros'] ?? [];
        if (!is_array($rows) || count($rows) <= 1) {
            // Siempre dejar al menos una fila
            return;
        }
        unset($rows[$index]);
        $this->state['rubros'] = array_values($rows);

        // Reindexar las opciones por fila para que coincidan con los índices nuevos
        $newMadres = [];
        $newSubs   = [];
        foreach (array_keys($this->state['rubros']) as $i) {
            $newMadres[$i] = $this->madresOptions[$i] ?? [];
            $newSubs[$i]   = $this->subsOptions[$i] ?? [];
        }
        $this->madresOptions = $newMadres;
        $this->subsOptions   = $newSubs;
    }

    /**
     * Reconstruye las opciones de "madres" y "subs" según lo que haya en state['rubros'].
     * Llamalo después de setear $this->state['rubros'] (por ej. en editaComercio).
     */
    public function buildRubrosOptionsFromState(): void
    {
        $rows = $this->state['rubros'] ?? [];
        $this->madresOptions = [];
        $this->subsOptions   = [];

        foreach ($rows as $i => $row) {
            $mega  = (string)($row['mega']  ?? '');
            $madre = (string)($row['madre'] ?? '');

            // Madres para el mega actual
            $this->madresOptions[$i] = $mega !== ''
                ? \App\Models\Rubro::where('mega_rubro', $mega)
                    ->select('rubro_madre')->distinct()->orderBy('rubro_madre')
                    ->pluck('rubro_madre')->toArray()
                : [];

            // Subs para mega+madre actuales
            $this->subsOptions[$i] = ($mega !== '' && $madre !== '')
                ? \App\Models\Rubro::where('mega_rubro', $mega)
                    ->where('rubro_madre', $madre)
                    ->orderBy('subrubro')
                    ->get(['id','subrubro'])
                    ->map(fn($x)=>['id'=>$x->id,'sub'=>$x->subrubro])
                    ->toArray()
                : [];
        }
    }


    /**Crear*/
    public function createCliente()
    {
        $this->aplicarFlagsEstadoEnState();

        // Compat: si vienen rubros múltiples, setear rubro_id = primer sub_id (para reglas legacy)
        if (empty($this->state['rubro_id'] ?? null)) {
            $firstSub = collect($this->state['rubros'] ?? [])->pluck('sub_id')->filter()->first();
            if ($firstSub) $this->state['rubro_id'] = (int)$firstSub;
        }

        // Reglas base + fechas
        $reglas = array_merge($this->reglasComunes(false), $this->reglasFechasPorEstado(true));

        // ===== Punto 3.5: reglas extra para repeaters =====
        $rulesExtra = [
            'state.telefonos'               => ['array','min:1'],
            'state.telefonos.*'             => ['nullable','regex:/^[\d\s()+\-]{6,20}$/'],

            'state.rubros'                  => ['array','min:1'],
            'state.rubros.*.sub_id'         => ['required','exists:rubros,id'],

            'state.disposiciones'           => ['array'],
            'state.disposiciones.*.numero'  => ['nullable','string','max:60'],
            'state.disposiciones.*.fecha'   => ['nullable','date'],

            'state.habilitaciones'          => ['array'],
            'state.habilitaciones.*.numero' => ['nullable','string','max:60'],
            'state.habilitaciones.*.fecha'  => ['nullable','date'],
        ];
        $reglas = array_merge($reglas, $rulesExtra);

        // Validar
        $validated = $this->validate($reglas, $this->mensajes(), $this->atributos());
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

        // DNI/CUIT normalizado
        $data['dni_cuit'] = preg_replace('/\D/', '', $data['dni_cuit'] ?? '');

        // Campos que no guardás
        unset($data['domicilio_responsable'], $data['nomenclatura']);

        // Enriquecer geodatos
        $enricher = app(\App\Services\UbicacionGeoEnricher::class);
        $data = $enricher->enrich($data);

        // Crear Ubicación
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

        // ===== Persistencia de repeaters =====

        // RUBROS (pivot) + compat rubro_id
        $rubrosIds = collect($this->state['rubros'] ?? [])
            ->pluck('sub_id')->filter()->unique()->values()->all();
        $ubic->rubros()->sync($rubrosIds);
        $ubic->rubro_id = $rubrosIds[0] ?? null; // compat con legacy
        $ubic->save();

        // TELÉFONOS
        $telSan = collect($this->state['telefonos'] ?? [])
            ->map(fn($t)=>trim((string)$t))
            ->filter(fn($t)=>$t !== '')
            ->unique()
            ->values();
        foreach ($telSan as $t) {
            $ubic->telefonos()->create(['telefono'=>$t]);
        }

        // DISPOSICIONES
        foreach (($this->state['disposiciones'] ?? []) as $d) {
            $num = trim((string)($d['numero'] ?? ''));
            if ($num === '') continue;
            $ubic->disposiciones()->create([
                'numero' => $num,
                'fecha'  => !empty($d['fecha']) ? $d['fecha'] : null,
            ]);
        }

        // HABILITACIONES
        foreach (($this->state['habilitaciones'] ?? []) as $h) {
            $num = trim((string)($h['numero'] ?? ''));
            if ($num === '') continue;
            $ubic->habilitaciones()->create([
                'numero' => $num,
                'fecha'  => !empty($h['fecha']) ? $h['fecha'] : null,
            ]);
        }

        // Reset UI
        $this->resetPage();
        $this->reset('state');
        $this->dispatch('hide-form', ['message' => 'Comercio creado correctamente.']);
    }


    private function normalizarEstado(?string $estado): string
    {
        $e = trim(mb_strtolower($estado ?? ''));
        return match ($e) {
            'en tramite', 'en trámite', 'en_tramite', 'en-tramite' => 'entramite',
            'vigente' => 'vigente',
            'irregular' => 'irregular',
            'baja' => 'baja',
            default => 'entramite',
        };
    }


    /** Actualizar */
    public function updateComercio()
    {
        // Normalizaciones previas
        $this->state['estado'] = $this->normalizarEstado($this->state['estado'] ?? null);
        $this->aplicarFlagsEstadoEnState();

        // Compat: si vienen rubros múltiples, setear rubro_id = primer sub_id (para reglas legacy)
        if (empty($this->state['rubro_id'] ?? null)) {
            $firstSub = collect($this->state['rubros'] ?? [])->pluck('sub_id')->filter()->first();
            if ($firstSub) $this->state['rubro_id'] = (int)$firstSub;
        }

        unset($this->state['situacion']);

        // Reglas base + fechas
        $reglas = array_merge($this->reglasComunes(true), $this->reglasFechasPorEstado(false));
        unset($reglas['state.situacion']);

        // ===== Punto 3.5: reglas extra para repeaters =====
        $rulesExtra = [
            'state.telefonos'               => ['array','min:1'],
            'state.telefonos.*'             => ['nullable','regex:/^[\d\s()+\-]{6,20}$/'],

            'state.rubros'                  => ['array','min:1'],
            'state.rubros.*.sub_id'         => ['required','exists:rubros,id'],

            'state.disposiciones'           => ['array'],
            'state.disposiciones.*.numero'  => ['nullable','string','max:60'],
            'state.disposiciones.*.fecha'   => ['nullable','date'],

            'state.habilitaciones'          => ['array'],
            'state.habilitaciones.*.numero' => ['nullable','string','max:60'],
            'state.habilitaciones.*.fecha'  => ['nullable','date'],
        ];
        $reglas = array_merge($reglas, $rulesExtra);

        // Validar
        $validated = $this->validate($reglas, $this->mensajes(), $this->atributos());
        $data = $validated['state'];

        // Normalizaciones varias
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $c) {
            if (!empty($data[$c])) $data[$c] = Str::title($data[$c]);
        }
        $esFisica = ($data['persona_tipo'] ?? 'fisica') === 'fisica';
        if ($esFisica) {
            $data['razon_social'] = $data['razon_social'] ?? null;
        } else {
            $data['apellido'] = $data['apellido'] ?? null;
            $data['nombres']  = $data['nombres']  ?? null;
        }

        // Re-geocodificar si cambió la dirección
        $direccionVieja = trim((string)$this->ubicacion->getOriginal('domicilio_comercio'));
        $direccionNueva = trim((string)($data['domicilio_comercio'] ?? ''));
        if ($direccionNueva !== '' && $direccionNueva !== $direccionVieja) {
            $enricher = app(\App\Services\UbicacionGeoEnricher::class);
            $data = $enricher->enrich($data);
        }

        // Limpiezas que NO guardás
        $data['dni_cuit'] = preg_replace('/\D/', '', $data['dni_cuit'] ?? '');
        unset($data['domicilio_responsable'], $data['nomenclatura']);

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
        $permitidos = array_flip((new \App\Models\UbicacionDocumento)->getFillable());
        $documentos = array_intersect_key($documentos, $permitidos);

        // Guardar Ubicación
        $this->ubicacion->update($data);

        // Guardar checklist
        $this->ubicacion->documentos()->updateOrCreate(
            ['ubicacion_id' => $this->ubicacion->id],
            array_merge($this->docDefaults, $documentos, ['ubicacion_id' => $this->ubicacion->id])
        );

        // ===== Persistencia de repeaters =====

        // RUBROS (pivot) + compat rubro_id
        $rubrosIds = collect($this->state['rubros'] ?? [])
            ->pluck('sub_id')->filter()->unique()->values()->all();
        $this->ubicacion->rubros()->sync($rubrosIds);
        $this->ubicacion->rubro_id = $rubrosIds[0] ?? null; // compat con legacy
        $this->ubicacion->save();

        // TELÉFONOS (reescritura simple)
        $this->ubicacion->telefonos()->delete();
        $telSan = collect($this->state['telefonos'] ?? [])
            ->map(fn($t)=>trim((string)$t))
            ->filter(fn($t)=>$t !== '')
            ->unique()
            ->values();
        foreach ($telSan as $t) {
            $this->ubicacion->telefonos()->create(['telefono'=>$t]);
        }

        // DISPOSICIONES
        $this->ubicacion->disposiciones()->delete();
        foreach (($this->state['disposiciones'] ?? []) as $d) {
            $num = trim((string)($d['numero'] ?? ''));
            if ($num === '') continue;
            $this->ubicacion->disposiciones()->create([
                'numero' => $num,
                'fecha'  => !empty($d['fecha']) ? $d['fecha'] : null,
            ]);
        }

        // HABILITACIONES
        $this->ubicacion->habilitaciones()->delete();
        foreach (($this->state['habilitaciones'] ?? []) as $h) {
            $num = trim((string)($h['numero'] ?? ''));
            if ($num === '') continue;
            $this->ubicacion->habilitaciones()->create([
                'numero' => $num,
                'fecha'  => !empty($h['fecha']) ? $h['fecha'] : null,
            ]);
        }

        // Notificar (mapa, etc.)
        $this->dispatch('ubicacion-actualizada', id: $this->ubicacion->id);

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
        // IMPORTANTE: NO tocar fecha_vto; queda manual
    }

    private function reglasFechasPorEstado(bool $esCreate): array
    {
        $nuevo = $this->normalizarEstado($this->state['estado'] ?? null);

        $reglas = [
            'state.fecha_alta' => 'nullable|date',
            'state.fecha_baja' => 'nullable|date',
            'state.fecha_vto'  => 'nullable|date',
        ];

        switch ($nuevo) {
            case 'entramite':
                // Nunca exige fechas
                break;

            case 'vigente':
                if ($esCreate) {
                    $reglas['state.fecha_alta'] = 'required|date';
                } else {
                    $prevNorm       = $this->normalizarEstado($this->ubicacion?->getOriginal('estado') ?? null);
                    $yaTeniaAlta    = !empty($this->ubicacion?->fecha_alta);
                    $vieneAltaAhora = !empty($this->state['fecha_alta']);

                    // Solo si pasás de EN TRÁMITE -> VIGENTE y no había alta ni viene ahora
                    if ($prevNorm === 'entramite' && !$yaTeniaAlta && !$vieneAltaAhora) {
                        $reglas['state.fecha_alta'] = 'required|date';
                    }
                }
                break;

            case 'irregular':
                // Irregular siempre con alta
                $reglas['state.fecha_alta'] = 'required|date';
                break;

            case 'baja':
                $tieneAltaAntes = !empty($this->ubicacion?->fecha_alta) || !empty($this->state['fecha_alta']);

                $reglas['state.fecha_baja'] = 'required|date'
                    . ($tieneAltaAntes ? '|after_or_equal:state.fecha_alta' : '')
                    . '|before_or_equal:today';

                if (empty($this->state['fecha_alta']) && empty($this->ubicacion?->fecha_alta)) {
                    $reglas['state.fecha_alta'] = 'required|date|before_or_equal:today';
                }

                $reglas['state.fecha_vto'] = 'nullable';
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
