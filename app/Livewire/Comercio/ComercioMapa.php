<?php

namespace App\Livewire\Comercio;

use App\Livewire\Admin\AdminComponent;
use App\Models\Ubicacion;
use App\Models\Rubro;
use App\Services\GeoService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use App\Models\UbicacionDocumento;
use App\Models\ComercioEstado;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ComercioMapa extends AdminComponent
{
    public array $barrios = [];
    public array $estados = ['entramite' => 'En trámite', 'vigente' => 'Vigente', 'irregular' => 'Irregular', 'baja' => 'Baja'];

    public string $fantasiaQuery = '';
    public array $fantasiaSuggestions = [];

    public array $rubroOpts = [];
    public ?int $selectedRubroId = null;

    public array $nomenOpts = [];
    public string $selectedNomen = '';

    public string $selectedBarrio = '';
    public string $selectedEstado = '';

    public array $ubicaciones = [];
    public $state = ['tipo_hab' => 'prev', 'documentos' => []];
    public $showEditModal = false;
    public string $formKey = '';

    public array $anexoOpts = [];

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

    #[On('open-create-from-map')]
    public function openCreateFromMap($payload): void
    {
        // Estado base del form (copiado del "nuevo" de Ubicaciones)
        $this->state = [
            'persona_tipo' => 'fisica',
            'tipo_hab'     => 'prev',
            'estado'       => null,
            'fecha_alta'   => null,
            'fecha_baja'   => null,
            'fecha_vto'    => null,
            'rubro_id'     => null,
            'dni_cuit'     => '',
            'apellido'     => '',
            'nombres'      => '',
            'razon_social' => '',
            'nombre_comercial' => '',
            'domicilio_comercio'=> $payload['direccion'] ?? '',
            'barrio'            => $payload['barrio'] ?? '',
            'nomenclatura'      => $payload['nomen'] ?? '',
            'correo'       => '',
            'telefono'     => '',
            'monto_pagar'  => null,
            'observaciones'=> '',
            'telefonos'    => [''],
            'rubros_anexos'=> [],
            'disposiciones'=> [['numero'=>'','fecha'=>null]],
            'habilitaciones'=>[['numero'=>'','fecha'=>null]],
            'documentos'   => [], // o tus defaults
        ];

        $this->formKey = (string) \Illuminate\Support\Str::uuid();
        $this->showEditModal = false;

        // Mostramos el modal (el JS del form escucha 'show-form')
        $this->dispatch('show-form', rubroId: ($this->state['rubro_id'] ?? null), anexos: ($this->state['rubros_anexos'] ?? []));
    }

    public function mount(GeoService $geo)
    {
        abort_unless(Gate::allows('view-maps'), 403);

        $this->barrios = $geo->barriosList();

        $this->rubroOpts = Rubro::orderBy('subrubro')->get(['id','subrubro'])->toArray();

        $this->nomenOpts = $this->leerNomenclaturas();

        $this->emitUbicaciones();
        $this->rubroOpts = Rubro::orderBy('subrubro')->get(['id','subrubro'])->toArray();
        $this->anexoOpts = $this->rubroOpts;

        $this->docDefaults = array_fill_keys(
            array_merge($this->docKeysGeneral, $this->docKeysJuridica),
            false
        );
        $this->state['documentos'] = $this->state['documentos'] ?? [];
        $this->formKey = (string) \Illuminate\Support\Str::uuid();
    }

    private function leerNomenclaturas(): array
    {
        $path = public_path('geo/CATASTRO_GEO.json');
        if (!is_file($path)) return [];
        $fc = json_decode(file_get_contents($path), true);
        $feats = $fc['features'] ?? [];
        $vals = [];
        foreach ($feats as $f) {
            $p = $f['properties'] ?? [];
            $nom = $p['RefName'] ?? ($p['NOMEN'] ?? ($p['NOMENC'] ?? ($p['NOMENCLATURA'] ?? null)));
            if ($nom) $vals[$nom] = true;
        }
        $arr = array_keys($vals);
        natsort($arr);
        return array_values($arr);
    }

    // ===== filtros de texto
    public function updatedFantasiaQuery($value)
    {
        $value = trim((string)$value);
        if ($value === '' || mb_strlen($value) < 2) {
            $this->fantasiaSuggestions = [];
            $this->emitUbicaciones();
            return;
        }

        $t = '%'.$value.'%';
        $this->fantasiaSuggestions = Ubicacion::query()
            ->whereNotNull('nombre_comercial')->where('nombre_comercial','<>','')
            ->where('nombre_comercial','like',$t)
            ->orderBy('nombre_comercial')->limit(10)
            ->pluck('nombre_comercial')->toArray();

        $this->emitUbicaciones();
    }

    // ===== rubro / barrio / estado / nomen
    public function updatedSelectedRubroId() { $this->emitUbicaciones(); }
    public function updatedSelectedBarrio()  { $this->emitUbicaciones(); }
    public function updatedSelectedEstado()  { $this->emitUbicaciones(); }
    public function updatedSelectedNomen()   { $this->emitUbicaciones(); } // para resaltar + zoom

    private function queryUbicaciones()
    {
        $subId    = $this->selectedRubroId ?: null;
        $fantasia = trim($this->fantasiaQuery ?? '');

        return Ubicacion::with('rubro:id,mega_rubro,rubro_madre,subrubro')
            ->when($subId, fn($q)=>$q->where('rubro_id',$subId))
            ->when($this->selectedBarrio !== '', fn($q)=> $q->where('barrio',$this->selectedBarrio))
            ->when($this->selectedEstado !== '', fn($q)=> $q->where('estado',$this->selectedEstado))
            ->when($fantasia !== '', function($q) use ($fantasia) {
                $t = '%'.$fantasia.'%';
                $q->where('nombre_comercial','like',$t);
            })
            ->orderByRaw("COALESCE(NULLIF(nombre_comercial,''), razon_social) asc")
            ->get([
                'id','razon_social','nombre_comercial','domicilio_comercio',
                'lat','lng','rubro_id','barrio','estado'
            ])
            ->map(function ($u) {
                return [
                    'id'               => $u->id,
                    'razon_social'     => $u->razon_social,
                    'nombre_comercial' => $u->nombre_comercial,
                    'domicilio_comercio'=> $u->domicilio_comercio,
                    'lat'              => $u->lat,
                    'lng'              => $u->lng,
                    'barrio'           => $u->barrio,
                    'estado'           => $u->estado,
                    'rubro'            => [
                        'id' => $u->rubro?->id,
                        'subrubro' => $u->rubro?->subrubro,
                    ],
                ];
            })->values();
    }

    private function emitUbicaciones(): void
    {
        $rows = $this->queryUbicaciones();
        $this->ubicaciones = $rows->toArray();
        $this->dispatch('ubicacionesUpdated', ubicaciones: $this->ubicaciones, selectedNomen: $this->selectedNomen);
    }

    #[On('ubicacion-actualizada')]
    public function onUbicacionActualizada(): void
    {
        $this->emitUbicaciones();
    }

    // ===================== helpers UI del form =====================
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
        if (!is_array($tels) || count($tels) <= 1) return;
        unset($tels[$index]);
        $this->state['telefonos'] = array_values($tels);
    }
    public function marcarTodosLosDocs(bool $valor = true): void
    {
        $docs = $this->state['documentos'] ?? [];
        foreach ($this->docKeysGeneral as $k) { $docs[$k] = $valor; }
        if (($this->state['persona_tipo'] ?? 'fisica') === 'juridica') {
            foreach ($this->docKeysJuridica as $k) { $docs[$k] = $valor; }
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

    // ===================== abrir modal de NUEVO (vacío) =====================
    public function nuevoComercio()
    {
        $this->reset('state');
        $this->state = [
            'persona_tipo'         => 'fisica',
            'estado'               => null,
            'tipo_hab'             => 'prev',
            'fecha_alta'           => null,
            'fecha_baja'           => null,
            'fecha_vto'            => null,
            'rubro_id'             => null,
            'dni_cuit'             => '',
            'apellido'             => '',
            'nombres'              => '',
            'razon_social'         => '',
            'nombre_comercial'     => '',
            'domicilio_responsable'=> '',
            'domicilio_comercio'   => '',
            'correo'               => '',
            'telefono'             => '',
            'nomenclatura'         => '',
            'monto_pagar'          => null,
            'observaciones'        => '',
            'telefonos'            => [''],
            'rubros_anexos'        => [],
            'disposiciones'        => [['numero'=>'', 'fecha'=>null]],
            'habilitaciones'       => [['numero'=>'', 'fecha'=>null]],
            'documentos'           => $this->docDefaults,
        ];
        $this->formKey = (string) \Illuminate\Support\Str::uuid();
        $this->showEditModal = false;

        $this->dispatch('show-form',
            rubroId: ($this->state['rubro_id'] ?? null),
            anexos:  ($this->state['rubros_anexos'] ?? [])
        );
    }

    // ===================== abrir modal prellenado DESDE MAPA =====================
    public function prefillAndOpenForm(?string $direccion = null, ?string $barrio = null, ?string $nomen = null): void
    {
        $this->nuevoComercio();
        if ($direccion) $this->state['domicilio_comercio'] = $direccion;
        if ($nomen)     $this->state['nomenclatura']      = $nomen;
        // (opcional) si querés mostrar barrio en el form: $this->state['barrio'] = $barrio;

        // re-disparar para asegurar que TomSelect tome valores
        $this->dispatch('show-form',
            rubroId: ($this->state['rubro_id'] ?? null),
            anexos:  ($this->state['rubros_anexos'] ?? [])
        );
    }

    // ===================== validaciones/aux =====================
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
    private function aplicarFlagsEstadoEnState(): void
    {
        $codigo = $this->state['estado'] ?? 'entramite';
        $estado = ComercioEstado::find($codigo);
        if (!$estado) return;
        if (!$estado->aplica_fecha_alta) $this->state['fecha_alta'] = null;
        if (!$estado->aplica_fecha_baja) $this->state['fecha_baja'] = null;
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
                break;
            case 'vigente':
                if ($esCreate) {
                    $reglas['state.fecha_alta'] = 'required|date';
                }
                break;
            case 'irregular':
                $reglas['state.fecha_alta'] = 'required|date';
                break;
            case 'baja':
                $tieneAltaAntes = !empty($this->state['fecha_alta']);
                $reglas['state.fecha_baja'] = 'required|date'
                    . ($tieneAltaAntes ? '|after_or_equal:state.fecha_alta' : '')
                    . '|before_or_equal:today';
                if (empty($this->state['fecha_alta'])) {
                    $reglas['state.fecha_alta'] = 'required|date|before_or_equal:today';
                }
                $reglas['state.fecha_vto'] = 'nullable';
                break;
        }
        return $reglas;
    }
    private function reglasComunes(bool $isUpdate = false): array
    {
        $rules = [
            'state.persona_tipo' => ['required', Rule::in(['fisica','juridica'])],
            'state.dni_cuit'     => ['bail','required','string','regex:/^\d{7,8}$|^\d{2}-\d{7,8}-\d{1}$|^\d{11}$/', function ($attr, $value, $fail) {
                if (strlen(preg_replace('/\D/','', $value)) === 11 && !$this->isValidCuit($value)) $fail('El CUIT no es válido.');
            }],
            'state.rubro_id'     => ['required','exists:rubros,id'],
            'state.apellido'     => ['nullable','string','min:2','max:60'],
            'state.nombres'      => ['nullable','string','min:2','max:80'],
            'state.razon_social' => ['nullable','string','min:2','max:120'],
            'state.nombre_comercial' => ['nullable','string','min:2','max:120'],
            'state.domicilio_responsable' => ['nullable','string','min:3','max:160'],
            'state.domicilio_comercio'    => ['nullable','string','min:3','max:160'],
            'state.correo'       => ['nullable','email:rfc,dns','max:120'],
            'state.telefono'     => ['nullable','regex:/^[\d\s()+\-]{6,20}$/'],
            'state.nomenclatura' => ['nullable','string','max:80'],
            'state.monto_pagar'  => ['nullable','numeric','min:0','regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'state.observaciones'=> ['nullable','string','max:500'],
            'state.estado'       => ['required', Rule::in(['entramite','vigente','irregular','baja'])],
            'state.tipo_hab'     => ['required', Rule::in(['definitiva','prev'])],
            'state.fecha_alta'   => ['nullable','date'],
            'state.fecha_baja'   => ['nullable','date'],
            'state.fecha_vto'    => ['nullable','date'],
            'state.documentos'   => ['array'],
        ];
        foreach (array_keys($this->docDefaults) as $key) {
            $rules["state.documentos.$key"] = ['boolean'];
        }
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
            'state.persona_tipo.required'  => 'Seleccioná el tipo de persona.',
            'state.persona_tipo.in'        => 'El tipo debe ser física o jurídica.',
            'state.dni_cuit.required'      => 'Ingresá DNI o CUIT.',
            'state.dni_cuit.regex'         => 'Usá DNI (7–8 dígitos) o CUIT (11 dígitos).',
            'state.rubro_id.required'      => 'Seleccioná el subrubro.',
            'state.rubro_id.exists'        => 'El subrubro seleccionado no es válido.',
            'state.apellido.required'      => 'El apellido es obligatorio.',
            'state.nombres.required'       => 'Los nombres son obligatorios.',
            'state.razon_social.required'  => 'La razón social es obligatoria.',
            'state.correo.email'           => 'Ingresá un correo válido.',
            'state.telefono.regex'         => 'Formato de teléfono inválido.',
            'state.monto_pagar.numeric'    => 'El monto debe ser numérico.',
            'state.monto_pagar.regex'      => 'Usá hasta 2 decimales (ej: 123.45).',
            'state.estado.required'        => 'Seleccioná el estado.',
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
    private function isValidCuit(string $cuit): bool
    {
        $cuit = preg_replace('/\D/','', $cuit);
        if (!preg_match('/^\d{11}$/', $cuit)) return false;
        $digits = array_map('intval', str_split($cuit));
        $weights = [5,4,3,2,7,6,5,4,3,2];
        $sum = 0; for ($i=0; $i<10; $i++) $sum += $digits[$i] * $weights[$i];
        $mod = $sum % 11;
        $check = $mod === 0 ? 0 : ($mod === 1 ? 9 : 11 - $mod);
        return $check === $digits[10];
    }

    public function crearDesdeMapaConDatos(?string $direccion = null, ?string $barrio = null, ?string $nomen = null): void
    {
        // inicializá estado mínimo si hace falta
        $this->state = $this->state ?? [];
        $this->state['domicilio_comercio'] = $direccion ?? '';
        $this->state['barrio']             = $barrio ?? '';
        $this->state['nomenclatura']       = $nomen ?? '';

        // asegurá opciones del selector como en el form
        $opts = \App\Models\Rubro::orderBy('subrubro')->get(['id','subrubro'])->toArray();
        $this->rubroOpts = $opts;
        $this->anexoOpts = $opts;

        $this->formKey = (string) \Illuminate\Support\Str::uuid();

        // abrí el modal (el JS del form ya escucha 'show-form')
        $this->dispatch('show-form', rubroId: ($this->state['rubro_id'] ?? null), anexos: ($this->state['rubros_anexos'] ?? []));
    }

    // ===================== CREAR (idéntico contrato que en Ubicaciones) =====================
    public function createCliente()
    {
        $this->aplicarFlagsEstadoEnState();

        $reglas = array_merge($this->reglasComunes(false), $this->reglasFechasPorEstado(true));
        $rulesExtra = [
            'state.telefonos'               => ['array','min:1'],
            'state.telefonos.*'             => ['nullable','regex:/^[\d\s()+\-]{6,20}$/'],
            'state.rubro_id'                => ['required','exists:rubros,id'],
            'state.rubros_anexos'           => ['array'],
            'state.rubros_anexos.*'         => ['integer','exists:rubros,id','different:state.rubro_id','distinct'],
            'state.disposiciones'           => ['array'],
            'state.disposiciones.*.numero'  => ['nullable','string','max:60'],
            'state.disposiciones.*.fecha'   => ['nullable','date'],
            'state.habilitaciones'          => ['array'],
            'state.habilitaciones.*.numero' => ['nullable','string','max:60'],
            'state.habilitaciones.*.fecha'  => ['nullable','date'],
        ];
        $reglas = array_merge($reglas, $rulesExtra);

        $validated = $this->validate($reglas, $this->mensajes(), $this->atributos());
        $data = $validated['state'];

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

        $data['dni_cuit'] = preg_replace('/\D/', '', $data['dni_cuit'] ?? '');

        // NO guardamos estos dos directamente (mantengo tu lógica)
        unset($data['domicilio_responsable'], $data['nomenclatura']);

        // Enriquecer geodatos (incluye barrio + cpu por lat/lng/nomen)
        $enricher = app(\App\Services\UbicacionGeoEnricher::class);
        $data = $enricher->enrich($data);

        // Crear
        $ubic = Ubicacion::create($data);

        // Checklist (hasOne)
        $permitidos = array_flip((new UbicacionDocumento)->getFillable());
        $documentos = array_intersect_key($documentos, $permitidos);
        foreach ($permitidos as $campo => $_) {
            $documentos[$campo] = (bool)($documentos[$campo] ?? false);
        }
        $ubic->documentos()->updateOrCreate(
            ['ubicacion_id' => $ubic->id],
            array_merge($this->docDefaults, $documentos, ['ubicacion_id' => $ubic->id])
        );

        // Rubro principal + anexos
        $principal = (int)($this->state['rubro_id'] ?? 0);
        $anexos = collect($this->state['rubros_anexos'] ?? [])
            ->map(fn($v)=>(int)$v)->filter()->reject(fn($id)=>$id === $principal)->unique()->values()->all();
        $pivotIds = array_values(array_unique(array_merge([$principal], $anexos)));
        $ubic->rubros()->sync($pivotIds);
        $ubic->rubro_id = $principal ?: null;
        $ubic->save();

        // Teléfonos
        $telSan = collect($this->state['telefonos'] ?? [])
            ->map(fn($t)=>trim((string)$t))->filter(fn($t)=>$t !== '')->unique()->values();
        foreach ($telSan as $t) { $ubic->telefonos()->create(['telefono'=>$t]); }

        // Disposiciones
        foreach (($this->state['disposiciones'] ?? []) as $d) {
            $num = trim((string)($d['numero'] ?? '')); if ($num === '') continue;
            $ubic->disposiciones()->create([
                'numero' => $num,
                'fecha'  => !empty($d['fecha']) ? $d['fecha'] : null,
            ]);
        }

        // Habilitaciones
        foreach (($this->state['habilitaciones'] ?? []) as $h) {
            $num = trim((string)($h['numero'] ?? '')); if ($num === '') continue;
            $ubic->habilitaciones()->create([
                'numero' => $num,
                'fecha'  => !empty($h['fecha']) ? $h['fecha'] : null,
            ]);
        }

        // avisar al mapa/lista
        $this->dispatch('ubicacion-actualizada', id: $ubic->id);

        // limpiar UI
        $this->reset('state');
        $this->dispatch('hide-form', ['message' => 'Comercio creado correctamente.']);
    }


    public function render()
    {
        return view('livewire.comercio.comercio-mapa', [
            'barrios'     => $this->barrios,
            'estados'     => $this->estados,
            'ubicaciones' => $this->ubicaciones,
            'rubroOpts'   => $this->rubroOpts,
            'nomenOpts'   => $this->nomenOpts,
        ])->layout('admin.layouts.app');
    }

    public static string $layout = 'admin.layouts.app';
}
