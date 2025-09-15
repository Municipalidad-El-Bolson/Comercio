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

    public string $rubroQuery = '';
    public string $anexoQuery = '';
    public array $rubroOpts = [];
    public array $anexoOpts = [];

    public string $formKey = '';

    protected array $docKeysGeneral = [
        'doc_libre_deuda_municipal','doc_planeamiento_urbano','doc_solicitud_habilitacion_pago',
        'doc_comprobante_uso_local','doc_afip_constancia','doc_recaudacion_rn','doc_fotocopia_dni',
        'doc_comprobante_uso_inmueble','doc_libre_deuda_tasas_inmueble','doc_aptitud_tecnica_local',
        'doc_cocap_rhi','doc_nota_carteleria_obras','doc_libro_actas_100',
    ];
    protected array $docKeysJuridica = ['doc_acta_constitucion','doc_contrato_societario','doc_docs_representantes'];
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

    public function updatedRubroQuery(string $q): void
    {
        $q = trim($q);
        $this->rubroOpts = Rubro::when($q !== '', fn($qq)=>$qq->where('subrubro','like',"%{$q}%"))
            ->orderBy('subrubro')->limit(50)->get(['id','subrubro'])->toArray();
    }

    public function updatedAnexoQuery(string $q): void
    {
        $q = trim($q);
        $this->anexoOpts = Rubro::when($q !== '', fn($qq)=>$qq->where('subrubro','like',"%{$q}%"))
            ->orderBy('subrubro')->limit(50)->get(['id','subrubro'])->toArray();
    }

    public function mount()
    {
        abort_unless(Gate::allows('manage-ubicaciones'), 403);

        $this->rubroOpts = Rubro::orderBy('subrubro')->limit(50)->get(['id','subrubro'])->toArray();
        $this->anexoOpts = $this->rubroOpts;

        $this->docDefaults = array_fill_keys(array_merge($this->docKeysGeneral, $this->docKeysJuridica), false);
        $this->state['documentos'] = $this->state['documentos'] ?? [];
        $this->formKey = (string) \Illuminate\Support\Str::uuid();

        // Prefill desde mapa (lat/lng/nomen/barrio) y abrir formulario
        $req = request();
        if ($req->boolean('from_map')) {
            $this->nuevoComercio();
            if ($lat = $req->query('lat'))  $this->state['lat']  = (float)$lat;
            if ($lng = $req->query('lng'))  $this->state['lng']  = (float)$lng;
            if ($nom = $req->query('nomen')) $this->state['nomenclatura'] = (string)$nom;
            if ($bar = $req->query('barrio')) $this->state['barrio'] = (string)$bar;
            // Forzar reapertura del modal con los datos
            $this->dispatch('show-form', rubroId: ($this->state['rubro_id'] ?? null), anexos: ($this->state['rubros_anexos'] ?? []));
        }
        // Prefill desde mapa: ?open=create&lat=...&lng=...&nomen=...
        $open  = request('open');
        $lat   = request('lat');
        $lng   = request('lng');
        $nomen = request('nomen');

        if ($open === 'create') {
            $this->nuevoComercio();
            if ($nomen) $this->state['nomenclatura'] = (string)$nomen;
            if (is_numeric($lat)) $this->state['lat'] = (float)$lat;
            if (is_numeric($lng)) $this->state['lng'] = (float)$lng;

            $this->dispatch('show-form',
                rubroId: ($this->state['rubro_id'] ?? null),
                anexos: ($this->state['rubros_anexos'] ?? [])
            );
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
        ])->layout('admin.layouts.app');
    }

    public function nuevoComercio()
    {
        $this->reset('state', 'ubicacion');

        $this->rubroQuery = '';
        $this->anexoQuery = '';
        $opts = Rubro::orderBy('subrubro')->limit(50)->get(['id','subrubro'])->toArray();
        $this->rubroOpts = $opts;
        $this->anexoOpts = $opts;

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
            'lat'                  => null,
            'lng'                  => null,
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
        $this->dispatch('show-form', rubroId: ($this->state['rubro_id'] ?? null), anexos: ($this->state['rubros_anexos'] ?? []));
    }

    public function editaComercio(Ubicacion $ubicacion)
    {
        $this->showEditModal = true;

        $this->ubicacion = $ubicacion->loadMissing([
            'rubro','rubros','telefonos','disposiciones','habilitaciones','documentos',
        ]);

        $this->state = $this->ubicacion->toArray();

        foreach (['fecha_alta','fecha_baja','fecha_vto'] as $f) {
            $this->state[$f] = !empty($this->ubicacion->{$f}) ? $this->ubicacion->{$f}->format('Y-m-d') : null;
        }

        $this->state['estado']    = $this->normalizarEstado($this->state['estado'] ?? null);
        $this->state['situacion'] = $this->ubicacion->situacion ?? ($this->state['situacion'] ?? null);

        $principal = (int)($this->ubicacion->rubro_id ?? 0) ?: null;
        $this->state['rubro_id'] = $principal;

        $idsPivot = $this->ubicacion->rubros->pluck('id')->filter()->unique()->values()->all();
        $this->state['rubros_anexos'] = array_values($principal ? array_diff($idsPivot, [$principal]) : $idsPivot);

        if (empty($this->rubroOpts)) $this->rubroOpts = Rubro::orderBy('subrubro')->limit(50)->get(['id','subrubro'])->toArray();
        if (empty($this->anexoOpts)) $this->anexoOpts = $this->rubroOpts;

        $idsNecesarios = array_values(array_unique(array_filter(array_merge([$principal], $this->state['rubros_anexos']))));
        if (!empty($idsNecesarios)) {
            $seleccionados = Rubro::whereIn('id', $idsNecesarios)->orderBy('subrubro')->get(['id','subrubro'])->toArray();
            $this->rubroOpts = $this->mergeOpts($this->rubroOpts, $seleccionados);
            $this->anexoOpts = $this->mergeOpts($this->anexoOpts, $seleccionados);
        }

        $tels = $this->ubicacion->telefonos->pluck('telefono')->filter()->values()->all();
        $this->state['telefonos'] = !empty($tels) ? $tels : [''];

        $this->state['disposiciones'] = $this->ubicacion->disposiciones->map(fn($d)=>[
            'numero'=>(string)$d->numero,'fecha'=>$d->fecha ? $d->fecha->format('Y-m-d') : null,
        ])->values()->all();
        if (empty($this->state['disposiciones'])) $this->state['disposiciones'] = [['numero'=>'','fecha'=>null]];

        $this->state['habilitaciones'] = $this->ubicacion->habilitaciones->map(fn($h)=>[
            'numero'=>(string)$h->numero,'fecha'=>$h->fecha ? $h->fecha->format('Y-m-d') : null,
        ])->values()->all();
        if (empty($this->state['habilitaciones'])) $this->state['habilitaciones'] = [['numero'=>'','fecha'=>null]];

        $docs = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $this->state['documentos'] = array_merge($this->docDefaults, array_intersect_key($docs, $this->docDefaults));

        $this->formKey = (string) \Illuminate\Support\Str::uuid();
        $this->dispatch('show-form', rubroId: ($this->state['rubro_id'] ?? null), anexos: ($this->state['rubros_anexos'] ?? []));
    }

    private function mergeOpts(array $opts, array $extra): array
    {
        $byId = [];
        foreach ($opts as $op)  { $byId[$op['id']] = $op; }
        foreach ($extra as $op) { $byId[$op['id']] = $op; }
        return array_values($byId);
    }

    private function reglasComunes(bool $isUpdate = false): array
    {
        $rules = [
            'state.persona_tipo'          => ['required', Rule::in(['fisica','juridica'])],
            'state.dni_cuit'              => [
                'bail','required','string',
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
            'state.lat'                   => ['nullable','numeric','between:-90,90'],
            'state.lng'                   => ['nullable','numeric','between:-180,180'],
            'state.documentos'            => ['array'],
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
            'state.apellido.min'         => 'El apellido debe tener al menos :min caracteres.',
            'state.nombres.min'          => 'Los nombres deben tener al menos :min caracteres.',
            'state.nombre_comercial.min' => 'El nombre comercial debe tener al menos :min caracteres.',
            'state.persona_tipo.required'=> 'Seleccioná el tipo de persona.',
            'state.persona_tipo.in'      => 'El tipo debe ser física o jurídica.',
            'state.dni_cuit.required'    => 'Ingresá DNI o CUIT.',
            'state.dni_cuit.regex'       => 'Usá DNI (7–8 dígitos) o CUIT (11 dígitos).',
            'state.rubro_id.required'    => 'Seleccioná el subrubro.',
            'state.rubro_id.exists'      => 'El subrubro seleccionado no es válido.',
            'state.apellido.required'    => 'El apellido es obligatorio.',
            'state.nombres.required'     => 'Los nombres son obligatorios.',
            'state.razon_social.required'=> 'La razón social es obligatoria.',
            'state.telefono.regex'       => 'Formato de teléfono inválido.',
            'state.monto_pagar.numeric'  => 'El monto debe ser numérico.',
            'state.monto_pagar.regex'    => 'Usá hasta 2 decimales (ej: 123.45).',
            'state.estado.required'      => 'Seleccioná el estado.',
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
        if (!is_array($tels) || count($tels) <= 1) return;
        unset($tels[$index]);
        $this->state['telefonos'] = array_values($tels);
    }

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

        // NO eliminar nomenclatura (se guarda)
        unset($data['domicilio_responsable']);

        // Enriquecer geodatos (mantiene lat/lng si vienen, y completa barrio/nomenclatura)
        $enricher = app(\App\Services\UbicacionGeoEnricher::class);
        $data = $enricher->enrich($data);

        $ubic = Ubicacion::create($data);

        $permitidos = array_flip((new UbicacionDocumento)->getFillable());
        $documentos = array_intersect_key($documentos, $permitidos);
        foreach ($permitidos as $campo => $_) {
            $documentos[$campo] = (bool)($documentos[$campo] ?? false);
        }
        $ubic->documentos()->updateOrCreate(
            ['ubicacion_id' => $ubic->id],
            array_merge($this->docDefaults, $documentos, ['ubicacion_id' => $ubic->id])
        );

        $principal = (int)($this->state['rubro_id'] ?? 0);
        $anexos = collect($this->state['rubros_anexos'] ?? [])
            ->map(fn($v)=>(int)$v)->filter()->reject(fn($id)=>$id === $principal)->unique()->values()->all();
        $pivotIds = array_values(array_unique(array_merge([$principal], $anexos)));
        $ubic->rubros()->sync($pivotIds);
        $ubic->rubro_id = $principal ?: null;
        $ubic->save();

        $telSan = collect($this->state['telefonos'] ?? [])
            ->map(fn($t)=>trim((string)$t))->filter(fn($t)=>$t !== '')->unique()->values();
        foreach ($telSan as $t) $ubic->telefonos()->create(['telefono'=>$t]);

        foreach (($this->state['disposiciones'] ?? []) as $d) {
            $num = trim((string)($d['numero'] ?? '')); if ($num === '') continue;
            $ubic->disposiciones()->create(['numero'=>$num, 'fecha'=>!empty($d['fecha']) ? $d['fecha'] : null]);
        }

        foreach (($this->state['habilitaciones'] ?? []) as $h) {
            $num = trim((string)($h['numero'] ?? '')); if ($num === '') continue;
            $ubic->habilitaciones()->create(['numero'=>$num, 'fecha'=>!empty($h['fecha']) ? $h['fecha'] : null]);
        }

        $this->resetPage();
        $this->reset('state');
        $this->dispatch('hide-form', ['message' => 'Comercio creado correctamente.']);
    }

    private function normalizarEstado(?string $estado): string
    {
        $e = trim(mb_strtolower($estado ?? ''));
        return match ($e) {
            'en tramite','en trámite','en_tramite','en-tramite' => 'entramite',
            'vigente' => 'vigente',
            'irregular' => 'irregular',
            'baja' => 'baja',
            default => 'entramite',
        };
    }

    public function updateComercio()
    {
        $this->state['estado'] = $this->normalizarEstado($this->state['estado'] ?? null);
        $this->aplicarFlagsEstadoEnState();
        unset($this->state['situacion']);

        $reglas = array_merge($this->reglasComunes(true), $this->reglasFechasPorEstado(false));
        unset($reglas['state.situacion']);

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
        if ($esFisica) $data['razon_social'] = $data['razon_social'] ?? null;
        else { $data['apellido'] = $data['apellido'] ?? null; $data['nombres'] = $data['nombres'] ?? null; }

        $direccionVieja = trim((string)$this->ubicacion->getOriginal('domicilio_comercio'));
        $direccionNueva = trim((string)($data['domicilio_comercio'] ?? ''));
        if ($direccionNueva !== '' && $direccionNueva !== $direccionVieja) {
            $enricher = app(\App\Services\UbicacionGeoEnricher::class);
            $data = $enricher->enrich($data);
        }

        $data['dni_cuit'] = preg_replace('/\D/', '', $data['dni_cuit'] ?? '');
        // NO eliminar nomenclatura
        unset($data['domicilio_responsable']);

        $documentos = $data['documentos'] ?? [];
        unset($data['documentos']);
        if (array_key_exists('doc_afip_constancia', $documentos)) {
            if (($this->state['persona_tipo'] ?? 'fisica') === 'juridica')
                $documentos['doc_afip_constancia_juridica'] = (bool)$documentos['doc_afip_constancia'];
            else
                $documentos['doc_afip_constancia_fisica'] = (bool)$documentos['doc_afip_constancia'];
            unset($documentos['doc_afip_constancia']);
        }
        if (array_key_exists('doc_recaudacion_rn', $documentos)) {
            $documentos['doc_constancia_recaudacion'] = (bool)$documentos['doc_recaudacion_rn'];
            unset($documentos['doc_recaudacion_rn']);
        }
        $permitidos = array_flip((new \App\Models\UbicacionDocumento)->getFillable());
        $documentos = array_intersect_key($documentos, $permitidos);

        $this->ubicacion->update($data);

        $this->ubicacion->documentos()->updateOrCreate(
            ['ubicacion_id' => $this->ubicacion->id],
            array_merge($this->docDefaults, $documentos, ['ubicacion_id' => $this->ubicacion->id])
        );

        $principal = (int)($this->state['rubro_id'] ?? 0);
        $anexos = collect($this->state['rubros_anexos'] ?? [])
            ->map(fn($v)=>(int)$v)->filter()->reject(fn($id)=>$id === $principal)->unique()->values()->all();
        $pivotIds = array_values(array_unique(array_merge([$principal], $anexos)));

        $this->ubicacion->rubros()->sync($pivotIds);
        $this->ubicacion->rubro_id = $principal ?: null;
        $this->ubicacion->save();

        $this->ubicacion->telefonos()->delete();
        $telSan = collect($this->state['telefonos'] ?? [])
            ->map(fn($t)=>trim((string)$t))->filter(fn($t)=>$t !== '')->unique()->values();
        foreach ($telSan as $t) $this->ubicacion->telefonos()->create(['telefono'=>$t]);

        $this->ubicacion->disposiciones()->delete();
        foreach (($this->state['disposiciones'] ?? []) as $d) {
            $num = trim((string)($d['numero'] ?? '')); if ($num === '') continue;
            $this->ubicacion->disposiciones()->create(['numero'=>$num,'fecha'=>!empty($d['fecha']) ? $d['fecha'] : null]);
        }

        $this->ubicacion->habilitaciones()->delete();
        foreach (($this->state['habilitaciones'] ?? []) as $h) {
            $num = trim((string)($h['numero'] ?? '')); if ($num === '') continue;
            $this->ubicacion->habilitaciones()->create(['numero'=>$num,'fecha'=>!empty($h['fecha']) ? $h['fecha'] : null]);
        }

        $this->dispatch('ubicacion-actualizada', id: $this->ubicacion->id);
        $this->resetPage();
        $this->dispatch('hide-form', ['message' => 'Registro actualizado correctamente']);
    }

    public function marcarTodosLosDocs(bool $valor = true): void
    {
        $docs = $this->state['documentos'] ?? [];
        foreach ($this->docKeysGeneral as $k) $docs[$k] = $valor;
        if (($this->state['persona_tipo'] ?? 'fisica') === 'juridica') {
            foreach ($this->docKeysJuridica as $k) $docs[$k] = $valor;
        }
        $this->state['documentos'] = array_merge($this->docDefaults, $docs);
    }

    public function updatedStatePersonaTipo($tipo): void
    {
        if ($tipo === 'fisica') {
            $this->state['documentos'] = $this->state['documentos'] ?? [];
            foreach ($this->docKeysJuridica as $k) $this->state['documentos'][$k] = false;
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
                if ($esCreate) $reglas['state.fecha_alta'] = 'required|date';
                else {
                    $prevNorm       = $this->normalizarEstado($this->ubicacion?->getOriginal('estado') ?? null);
                    $yaTeniaAlta    = !empty($this->ubicacion?->fecha_alta);
                    $vieneAltaAhora = !empty($this->state['fecha_alta']);
                    if ($prevNorm === 'entramite' && !$yaTeniaAlta && !$vieneAltaAhora) {
                        $reglas['state.fecha_alta'] = 'required|date';
                    }
                }
                break;
            case 'irregular':
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
