<?php

namespace App\Livewire\Comercio;

use App\Livewire\Admin\AdminComponent;
use App\Models\Rubro;
use App\Models\Ubicacion;
use App\Models\ComercioEstado;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Support\HandlesEstados;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use App\Models\UbicacionEstadoHist;
use Illuminate\Support\Facades\Log;


class Ubicaciones extends AdminComponent
{
    use WithPagination;
    use HandlesEstados;

    public $searchTerm = '';
    public $state = ['tipo_hab' => 'prev', 'documentos' => []];
    public ?Ubicacion $ubicacion = null;
    public bool $showEditModal = false;

    public string $rubroQuery = '';
    public string $anexoQuery = '';
    public array $rubroOpts = [];
    public array $anexoOpts = [];
    public string $formKey = '';

    /** ======== Catálogo de documentos ======== */
    private array $docKeysGeneral = [
        'doc_libre_deuda_municipal','doc_planeamiento_urbano','doc_solicitud_habilitacion_pago',
        'doc_comprobante_uso_local','doc_afip_constancia','doc_recaudacion_rn','doc_fotocopia_dni',
        'doc_comprobante_uso_inmueble','doc_libre_deuda_tasas_inmueble','doc_aptitud_tecnica_local',
        'doc_cocap_rhi','doc_nota_carteleria_obras','doc_libro_actas_100',
    ];
    private array $docKeysJuridica = ['doc_acta_constitucion','doc_contrato_societario','doc_docs_representantes'];

    private array $docLabels = [
        // General
        'doc_libre_deuda_municipal' => 'Certificado de libre deuda municipal',
        'doc_planeamiento_urbano'   => 'Dirección de Planeamiento Urbano',
        'doc_solicitud_habilitacion_pago' => 'Solicitud de habilitación + pago',
        'doc_comprobante_uso_local' => 'Comprobante de uso del local',
        'doc_afip_constancia'       => 'Constancia de inscripción AFIP',
        'doc_recaudacion_rn'        => 'Constancia de inscripción Recaudación RN',
        'doc_fotocopia_dni'         => 'Fotocopia de DNI',
        'doc_comprobante_uso_inmueble' => 'Comprobante de uso del inmueble',
        'doc_libre_deuda_tasas_inmueble' => 'Libre deuda de tasas del inmueble',
        'doc_aptitud_tecnica_local' => 'Certificado de aptitud técnica',
        'doc_cocap_rhi'             => 'Certificado CO.CA.P.RHI',
        'doc_nota_carteleria_obras' => 'Nota a Obras por cartelería',
        'doc_libro_actas_100'       => 'Libro de actas (100 hojas)',
        // Jurídica
        'doc_acta_constitucion'     => 'Acta de constitución',
        'doc_contrato_societario'   => 'Contrato societario',
        'doc_docs_representantes'   => 'Documentación de representantes',
        // En trámite extra
        'doc_manipulacion_alimentos'=> 'Certificado de manipulación de alimentos',
        // Baja
        'doc_nota_baja'             => 'Nota de baja',
        'doc_pago_baja'             => 'Pago de baja',
        'doc_acta_inspeccion'       => 'Acta de inspección',
        // Irregular
        'doc_cert_electricidad'     => 'Certificado de electricidad',
        'doc_cert_gasista'          => 'Certificado de gasista',
        'doc_inf_seg_hig'           => 'Informe de seguridad e higiene',
        'doc_protocolo_mput'        => 'Protocolo de puesta a tierra',
        'doc_carga_fuego'           => 'Carga de fuego',
        'doc_inf_ascensores'        => 'Informe de ascensores',
        'doc_poliza_seguro'         => 'Póliza de seguro',
        'doc_cert_cocapri'          => 'Certificado CO.CA.P.R.I',
        'doc_inf_splif'             => 'Informe del SPLIF',
        'doc_control_plagas'        => 'Control de plagas',
        'doc_cert_caldera'          => 'Certificado de caldera',
        'doc_cert_zavecom'          => 'Certificado ZAVECOM',
        'doc_cert_salud_prov'       => 'Certificado de salud (Provincia)',
        // Flags derivados del select de uso
        'doc_uso_boleto'            => 'Uso: Boleto de compra-venta',
        'doc_uso_contrato'          => 'Uso: Contrato',
        'doc_uso_comodato'          => 'Uso: Comodato',
        'doc_uso_titulo'            => 'Uso: Título de propiedad',
        'doc_uso_cert_ocupacion'    => 'Uso: Certificado de ocupación',
    ];

    // Defaults (todos los booleanos en false; el select textual aparte)
    protected array $docDefaults = [];

    private function docKeysForEstado(string $estadoBase, bool $esJuridica): array
    {
        $baseGeneral = $this->docKeysGeneral;
        $juridica    = $esJuridica ? $this->docKeysJuridica : [];

        return match ($estadoBase) {
            '021' => array_values(array_unique(array_merge(
                array_diff($baseGeneral, [
                    'doc_nota_carteleria_obras',
                    'doc_planeamiento_urbano',
                    'doc_comprobante_uso_local'
                ]),
                ['doc_manipulacion_alimentos'],
                $juridica
            ))),
            '032' => [
                'doc_cert_electricidad','doc_cert_gasista','doc_inf_seg_hig','doc_protocolo_mput','doc_carga_fuego',
                'doc_inf_ascensores','doc_poliza_seguro','doc_cert_cocapri','doc_inf_splif','doc_control_plagas',
                'doc_cert_caldera','doc_cert_zavecom','doc_cert_salud_prov','doc_comprobante_uso_inmueble',
            ],
            '040' => [],
            'baja','baja_oficio','exp_sin_efecto' => [
                'doc_pago_baja',
                'doc_libre_deuda_municipal',
                'doc_acta_inspeccion',         
                'doc_nota_baja',
            ],
            default => array_merge($baseGeneral, $juridica),
        };
    }

    private function usoInmuebleOptions(): array
    {
        return [
            'boleto'         => 'Boleto de compra-venta',
            'contrato'       => 'Contrato',
            'comodato'       => 'Comodato',
            'titulo'         => 'Título de propiedad',
            'cert_ocupacion' => 'Certificado de ocupación',
        ];
    }

    public function getDocSchemaProperty(): array
    {
        $estado = $this->normalizarEstado($this->state['estado'] ?? '021');
        $esJuridica = ($this->state['persona_tipo'] ?? 'fisica') === 'juridica';
        $keys = $this->docKeysForEstado($estado, $esJuridica);

        $items = [];
        foreach ($keys as $k) {
            $items[] = ['key' => $k, 'label' => $this->docLabels[$k] ?? $k, 'type' => 'checkbox'];
        }

        $showUsoInmueble = in_array('doc_comprobante_uso_inmueble', $keys, true) || $estado === 'entramite';
        return [
            'items' => $items,
            'uso_inmueble' => [
                'show'        => $showUsoInmueble,
                'checkboxKey' => 'doc_comprobante_uso_inmueble',
                'selectKey'   => 'doc_uso_inmueble_tipo',
                'label'       => 'Uso de inmueble',
                'options'     => $this->usoInmuebleOptions(),
            ],
        ];
    }

    private function normalizarEstado(?string $estado): string
    {
        return $this->estadoBaseNormalize($estado);
    }

    public static function estadoLabels(): array
    {
        return [
            'entramite'  => '021',
            'vigente'    => 'Alta',
            'irregular'  => '032',
            'baja'       => 'Baja',
            'baja_oficio'=> 'Baja de oficio',
            'sin_efecto' => 'Expediente sin efecto',
        ];
    }

    private function normalizeDocsArray(array $docs): array
    {
        $textKeys = ['doc_uso_inmueble_tipo'];
        $out = [];
        foreach ($docs as $k => $v) {
            if (in_array($k, $textKeys, true)) {
                $vv = is_string($v) ? trim($v) : ($v === null ? null : (string)$v);
                $out[$k] = ($vv === '') ? null : $vv;
            } else {
                $out[$k] = (bool)$v;
            }
        }
        return $out;
    }

    private function normalizeDecimal($v): ?string
    {
        if ($v === null || $v === '') return null;
        $s = str_replace(' ', '', trim((string)$v));
        $hasDot = str_contains($s, '.'); $hasComma = str_contains($s, ',');
        if ($hasDot && $hasComma) {
            $lastDot = strrpos($s,'.'); $lastComma=strrpos($s,',');
            if ($lastComma > $lastDot) { $s=str_replace('.','',$s); $s=str_replace(',', '.', $s); }
            else { $s=str_replace(',', '', $s); }
        } elseif ($hasComma) { $s=str_replace(',', '.', $s); }
        if (!is_numeric($s)) return null;
        return number_format((float)$s, 2, '.', '');
    }

    private function calcularSituacion(?string $estado, bool $esClausurado): ?string
    {
        if ($esClausurado) return 'clausurado';
        $estado = $this->normalizarEstado($estado ?? '');
        return match ($estado) {
            'vigente'   => 'alta',
            'baja'      => 'baja',
            default     => null,
        };
    }

    /** ======== Ciclo de vida ======== */
    public function mount()
    {
        abort_unless(Gate::allows('manage-ubicaciones'), 403);

        $this->rubroOpts = Rubro::orderBy('subrubro')->get(['id','subrubro'])->toArray();
        $this->anexoOpts = $this->rubroOpts;

        // Defaults: todas las claves booleanas en false
        $boolKeys = array_keys($this->docLabels);
        $this->docDefaults = array_fill_keys($boolKeys, false);
        $this->state['documentos'] = $this->state['documentos'] ?? [];
        $this->formKey = (string) Str::uuid();

        // Prefills opcionales desde query
        $req = request();
        if ($req->boolean('from_map')) {
            $this->nuevoComercio();
            if ($lat = $req->query('lat'))  $this->state['lat']  = (float)$lat;
            if ($lng = $req->query('lng'))  $this->state['lng']  = (float)$lng;
            if ($nom = $req->query('nomen')) $this->state['nomenclatura'] = (string)$nom;
            if ($bar = $req->query('barrio')) $this->state['barrio'] = (string)$bar;
            $this->dispatch('show-form', rubroId: ($this->state['rubro_id'] ?? null), anexos: ($this->state['rubros_anexos'] ?? []));
        }

        if (request('open') === 'create') {
            $this->nuevoComercio();
            if (is_numeric(request('lat'))) $this->state['lat'] = (float)request('lat');
            if (is_numeric(request('lng'))) $this->state['lng'] = (float)request('lng');
            if ($n = request('nomen')) $this->state['nomenclatura'] = (string)$n;
            $this->dispatch('show-form', rubroId: ($this->state['rubro_id'] ?? null), anexos: ($this->state['rubros_anexos'] ?? []));
            $this->dispatch('refresh-selects', rubroId: ($this->state['rubro_id'] ?? null), anexos:  ($this->state['rubros_anexos'] ?? []));
        }

        if (request()->boolean('nuevo')) {
            $this->nuevoComercio();
            if ($d = request('domicilio'))     $this->state['domicilio_comercio'] = $d;
            if ($n = request('nomenclatura'))  $this->state['nomenclatura'] = $n;
            if ($b = request('barrio'))        $this->state['barrio'] = $b;

            $this->dispatch('show-form', rubroId: ($this->state['rubro_id'] ?? null), anexos:  ($this->state['rubros_anexos'] ?? []));
            $this->dispatch('refresh-selects', rubroId: ($this->state['rubro_id'] ?? null), anexos:  ($this->state['rubros_anexos'] ?? []));
        }
    }

    public function updatingSearchTerm() { $this->resetPage(); }

    public function render()
    {
        $t = '%'.$this->searchTerm.'%';

        $ubicaciones = Ubicacion::with(['rubro','estadoModel']) // <- removido 'habilitacionActual'
            ->where('nombre_comercial','like',$t)
            ->orderBy('nombre_comercial')
            ->paginate(10);

        return view('livewire.comercio.ubicaciones', [
            'ubicaciones' => $ubicaciones,
        ])->layout('admin.layouts.app');
    }

    /** ======== Form actions ======== */
    public function nuevoComercio()
    {
        $this->reset('state', 'ubicacion');

        $opts = Rubro::orderBy('subrubro')->get(['id','subrubro'])->toArray();
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
            'numero_disposicion'   => '',
            'numero_habilitacion'  => '',
            'cambio_tipo'          => '',
            'documentos'           => $this->docDefaults,
            'es_clausurado'        => false,
        ];

        $this->formKey = (string) Str::uuid();
        $this->showEditModal = false;
        $this->dispatch('show-form', rubroId: ($this->state['rubro_id'] ?? null), anexos: ($this->state['rubros_anexos'] ?? []));
    }

    private function estadoBaseNormalizeFromRaw(?string $raw): string
    {
        $s = trim(mb_strtolower((string)$raw));
        if ($s === '') return '021';

        // Compuestos tipo "021 - Cambio ..." / "032 - ..." / "040 - ..."
        if (str_starts_with($s, '021')) return '021';
        if (str_starts_with($s, '032')) return '032';
        if (str_starts_with($s, '040')) return '040';

        return match ($s) {
            'entramite','en tramite','en trámite','en_tramite','en-tramite','vigente','alta' => '021',
            'irregular'     => '032',
            '040','040/25'  => '040',
            'baja'          => 'baja',
            'baja de oficio','baja_oficio','baja-oficio' => 'baja_oficio',
            'expediente sin efecto','sin_efecto','exp_sin_efecto','exp-sin-efecto' => 'exp_sin_efecto',
            default         => '021',
        };
    }


    // Intenta inferir la key del cambio a partir del estado compuesto guardado
    private function inferCambioKeyFromEstado(string $estadoRaw, string $base): ?string
    {
        // si no hay guión, no hay cambio
        if (!str_contains($estadoRaw, '-')) return '';
        [$codigo, $label] = array_map('trim', explode('-', $estadoRaw, 2));
        if ($label === '') return '';

        $opts = $this->cambiosOptionsByBase($base);
        // Buscamos por label -> key (case-insensitive y tolerante a tildes mínimamente)
        foreach ($opts as $key => $lbl) {
            if (mb_strtolower($lbl) === mb_strtolower($label)) {
                return $key;
            }
        }
        return ''; // si no matchea, dejar “Ninguno”
    }


    public function editaComercio(Ubicacion $ubicacion)
    {
        $this->showEditModal = true;

        $this->ubicacion = $ubicacion->loadMissing([
            'rubro','rubros','telefonos','disposiciones','habilitaciones','documentos',
        ]);

        $this->state = $this->ubicacion->toArray();
        $this->state['es_clausurado'] = ($this->ubicacion->situacion === 'clausurado');

        $estadoCrudo = (string) ($this->ubicacion->getOriginal('estado') ?? $this->ubicacion->estado ?? '');
        $parsed = $this->parseCambioDesdeEstado($estadoCrudo);
        $this->state['cambio_tipo'] = $parsed['cambio_key'];

        $this->state['estado'] = $this->normalizarEstado($estadoCrudo ?: ($this->ubicacion->estado ?? 'entramite'));

        $estadoRaw = (string) ($this->ubicacion->estado ?? '');
        $base = $this->estadoBaseNormalizeFromRaw($estadoRaw);

        if (!array_key_exists('cambio_tipo', $this->state) || ($this->state['cambio_tipo'] ?? '') === '') {
            $this->state['cambio_tipo'] = $this->inferCambioKeyFromEstado($estadoRaw, $base);
        }

        $this->state['situacion'] = $this->ubicacion->situacion ?? ($this->state['situacion'] ?? null);

        $principal = (int)($this->ubicacion->rubro_id ?? 0) ?: null;
        $this->state['rubro_id'] = $principal;

        $idsPivot = $this->ubicacion->rubros->pluck('id')->filter()->unique()->values()->all();
        $this->state['rubros_anexos'] = array_values($principal ? array_diff($idsPivot, [$principal]) : $idsPivot);

        if (empty($this->rubroOpts)) $this->rubroOpts = Rubro::orderBy('subrubro')->get(['id','subrubro'])->toArray();
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

        // Documentos desde BD → normalizados → merge con defaults
        $docsDb = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $docsUi = $this->normalizeDocsArray($docsDb);
        $this->state['documentos'] = array_merge($this->docDefaults, $docsUi);
        $this->state['numero_disposicion']  = (string) data_get($this->state, 'disposiciones.0.numero', '');
        $this->state['numero_habilitacion'] = (string) data_get($this->state, 'habilitaciones.0.numero', '');

        $this->formKey = (string) Str::uuid();
        $this->dispatch('show-form', rubroId: ($this->state['rubro_id'] ?? null), anexos: ($this->state['rubros_anexos'] ?? []));
    }

    public function updatedStateNumeroDisposicion($val): void
    {
        $val = (string)($val ?? '');
        $disp = $this->state['disposiciones'] ?? [];
        if (!is_array($disp) || empty($disp)) $disp = [['numero'=>'','fecha'=>null]];
        $disp[0]['numero'] = $val;
        $this->state['disposiciones'] = array_values($disp);
    }

    public function updatedStateNumeroHabilitacion($val): void
    {
        $val = (string)($val ?? '');
        $hab = $this->state['habilitaciones'] ?? [];
        if (!is_array($hab) || empty($hab)) $hab = [['numero'=>'','fecha'=>null]];
        $hab[0]['numero'] = $val;
        $this->state['habilitaciones'] = array_values($hab);
    }

    /** ======== Utils ======== */
    private function mergeOpts(array $opts, array $extra): array
    {
        $byId = [];
        foreach ($opts as $op)  { $byId[$op['id']] = $op; }
        foreach ($extra as $op) { $byId[$op['id']] = $op; }
        return array_values($byId);
    }

    /** ======== Validación ======== */
    private function reglasComunes(bool $isUpdate = false): array
    {
        $rules = [
            'state.persona_tipo'          => ['required', Rule::in(['fisica','juridica'])],
            'state.dni_cuit'              => [
                'bail','required','string',
                'regex:/^\d{7,8}$|^\d{2}-\d{7,8}-\d{1}$|^\d{11}$/',
                function ($attr, $value, $fail) {
                    if (strlen(preg_replace('/\D/','', (string)$value)) === 11 && !$this->isValidCuit((string)$value)) {
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
            'state.monto_pagar'           => ['nullable','numeric','min:0'],
            'state.observaciones'         => ['nullable','string','max:500'],
            'state.estado'                => ['required', Rule::in(['entramite','irregular','baja','baja_oficio','sin_efecto','040'])],
            'state.tipo_hab'              => ['required', Rule::in(['definitiva','prev'])],
            'state.fecha_alta'            => ['nullable','date'],
            'state.fecha_baja'            => ['nullable','date'],
            'state.fecha_vto'             => ['nullable','date'],
            'state.lat'                   => ['nullable','numeric','between:-90,90'],
            'state.lng'                   => ['nullable','numeric','between:-180,180'],
            'state.documentos'            => ['array'],
            'state.es_clausurado'         => ['boolean'],
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
            'state.persona_tipo.required'=> 'Seleccioná el tipo de persona.',
            'state.rubro_id.required'    => 'Seleccioná el subrubro.',
            'state.estado.required'      => 'Seleccioná el estado.',
        ];
    }

    private function atributos(): array
    {
        return [
            'state.persona_tipo' => 'tipo de persona',
            'state.dni_cuit'     => 'DNI/CUIT',
            'state.estado'       => 'estado',
        ];
    }

    /** ======== Cambios dinámicos del form ======== */
    public function updatedStateEstado($nuevo): void
    {
        $estado = $this->normalizarEstado($nuevo);
        $esJuridica = ($this->state['persona_tipo'] ?? 'fisica') === 'juridica';
        $permitidos = $this->docKeysForEstado($estado, $esJuridica);

        $docs = $this->state['documentos'] ?? [];
        foreach (array_keys($this->docLabels) as $k) $docs[$k] = false;
        foreach ($permitidos as $k) $docs[$k] = (bool)($docs[$k] ?? false);
        $docs['doc_uso_inmueble_tipo'] = null;
        $this->state['cambio_tipo'] = '';
        $this->state['documentos'] = $docs;
    }

    public function updatedStatePersonaTipo($tipo): void
    {
        if ($tipo === 'fisica') {
            $this->state['documentos'] = $this->state['documentos'] ?? [];
            foreach ($this->docKeysJuridica as $k) $this->state['documentos'][$k] = false;
        }
    }

    public function marcarTodosLosDocs(bool $valor = true): void
    {
        $schema = $this->docSchema;
        $docs = $this->state['documentos'] ?? [];
        foreach ($schema['items'] as $it) {
            if ($it['type'] === 'checkbox') $docs[$it['key']] = $valor;
        }
        if (($schema['uso_inmueble']['show'] ?? false) && isset($schema['uso_inmueble']['checkboxKey'])) {
            $docs[$schema['uso_inmueble']['checkboxKey']] = $valor;
        }
        $this->state['documentos'] = $docs;
    }

    /** ======== CREATE ======== */
    public function createCliente()
    {

        // --- 0) Mapear estado del form a base/canónico/label antes de validar ---
        $rawEstado   = $this->state['estado'] ?? 'entramite';        // puede venir "021"/"032" o "entramite"/"irregular"
        $estadoBase  = $this->estadoBaseNormalize($rawEstado);       // => '021' | '032' | 'baja' | 'baja_oficio' | 'exp_sin_efecto'
        $estadoCanon = $this->mapBaseToCanon($estadoBase);           // => 'entramite' | 'irregular' | 'baja' | 'baja_oficio' | 'sin_efecto'
        $cambioKey   = (string)($this->state['cambio_tipo'] ?? '');
        $estadoLabel = $this->buildEstadoLabel($estadoBase, $cambioKey);

        // Poner el canónico en el state para que las reglas dependientes funcionen
        $tmpState = $this->state;
        $this->state['estado'] = $estadoCanon;

        // --- 1) Reglas: comunes + por-estado (si las tenés en el componente, reusarlas) ---
        $reglas = array_merge(
            $this->reglasComunes(false),
            $this->reglasFechasPorEstado(true),
            [
                'state.telefonos'               => ['array','min:1'],
                'state.telefonos.*'             => ['nullable','regex:/^[\d\s()+\-]{6,20}$/'],
                'state.rubros_anexos'           => ['array'],
                'state.rubros_anexos.*'         => ['integer','exists:rubros,id','different:state.rubro_id','distinct'],
                'state.disposiciones'           => ['array'],
                'state.disposiciones.*.numero'  => ['nullable','string','max:60'],
                'state.disposiciones.*.fecha'   => ['nullable','date'],
                'state.habilitaciones'          => ['array'],
                'state.habilitaciones.*.numero' => ['nullable','string','max:60'],
                'state.habilitaciones.*.fecha'  => ['nullable','date'],
            ]
        );

        $validated = \Validator::make(['state' => $tmpState], $reglas, $this->mensajes(), $this->atributos())->validate();
        $data = $this->state;

        if (array_key_exists('estado', $data)) {
            $data['estado'] = $this->mapBaseToCanon(
                $this->estadoBaseNormalize((string)$data['estado'])
            );
        }

        // --- 2) Normalizar strings / números ---
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $c) {
            if (!empty($data[$c] ?? null)) $data[$c] = Str::title($data[$c]);
        }
        $data['dni_cuit'] = preg_replace('/\D/','', $data['dni_cuit'] ?? '');
        if (array_key_exists('monto_pagar', $data)) {
            $data['monto_pagar'] = $this->normalizeDecimal($data['monto_pagar']);
        }
        if (($data['persona_tipo'] ?? 'fisica') === 'juridica') {
            $data['apellido'] = null; $data['nombres'] = null;
        }

        // Inyectar estado mapeado + situacion (si tenés checkbox de clausura en UI)
        $data['estado']       = $estadoCanon;     // FK válida a comercio_estados.codigo
        $data['estado_base']  = $estadoBase;      // 021/032/baja...
        $data['estado_label'] = $estadoLabel;     // "021 - Cambio de ..."

        if (in_array('situacion', Schema::getColumnListing('ubicaciones'), true)) {
            $data['situacion'] = $this->calcularSituacion($estadoCanon, (bool)($this->state['es_clausurado'] ?? false));
        }

        // Integrar helpers de número “sueltos” al primer item de repeaters
        $nd = trim((string)($this->state['numero_disposicion'] ?? ''));
        $nh = trim((string)($this->state['numero_habilitacion'] ?? ''));
        if (!empty($nd)) {
            if (empty($this->state['disposiciones']) || !is_array($this->state['disposiciones'])) {
                $this->state['disposiciones'] = [['numero'=>$nd, 'fecha'=>null]];
            } else {
                $this->state['disposiciones'][0]['numero'] = $nd;
            }
        }
        if (!empty($nh)) {
            if (empty($this->state['habilitaciones']) || !is_array($this->state['habilitaciones'])) {
                $this->state['habilitaciones'] = [['numero'=>$nh, 'fecha'=>null]];
            } else {
                $this->state['habilitaciones'][0]['numero'] = $nh;
            }
        }

        // --- 3) Limpiar campos que no van directo a ubicaciones ---
        unset($data['documentos'], $data['domicilio_responsable'], $data['es_clausurado']);

        // --- 4) Enriquecer geodatos si hay dirección ---
        if (!empty(trim((string)($data['domicilio_comercio'] ?? '')))) {
            $enricher = app(\App\Services\UbicacionGeoEnricher::class);
            $data = $enricher->enrich($data);
        }

        // --- 5) Filtrar a columnas reales antes de persistir ---
        $colsUbic = Schema::getColumnListing('ubicaciones');
        $data     = array_intersect_key($data, array_flip($colsUbic));

        // --- 6) Preparar colecciones auxiliares ---
        $docsFromUI = $this->state['documentos'] ?? [];
        $principal  = (int)($this->state['rubro_id'] ?? 0);
        $anexos     = collect($this->state['rubros_anexos'] ?? [])
                        ->map(fn($v)=>(int)$v)->filter()
                        ->reject(fn($id)=>$id === $principal)->unique()->values()->all();
        $telSan     = collect($this->state['telefonos'] ?? [])
                        ->map(fn($t)=>trim((string)$t))->filter(fn($t)=>$t!=='')->unique()->values();

        // --- 7) Persistencia en TX: Ubicacion + relaciones + historial ---
        DB::transaction(function () use ($data, $docsFromUI, $principal, $anexos, $telSan, $estadoBase, $estadoLabel) {
            // 7.1) Ubicación
            $ubic = \App\Models\Ubicacion::create($data);

            // 7.2) Documentos (normalizar + map exclusivo uso inmueble)
            $docs = $this->normalizeDocsArray($docsFromUI);
            $tipoUso = $docs['doc_uso_inmueble_tipo'] ?? null;
            foreach (['doc_uso_boleto','doc_uso_contrato','doc_uso_comodato','doc_uso_titulo','doc_uso_cert_ocupacion'] as $k) { $docs[$k] = false; }
            $mapUso = [
                'boleto'         => 'doc_uso_boleto',
                'contrato'       => 'doc_uso_contrato',
                'comodato'       => 'doc_uso_comodato',
                'titulo'         => 'doc_uso_titulo',
                'cert_ocupacion' => 'doc_uso_cert_ocupacion',
            ];
            if ($tipoUso && isset($mapUso[$tipoUso])) $docs[$mapUso[$tipoUso]] = true;

            $colsDocs = Schema::getColumnListing('ubicacion_documentos');
            $payload  = array_intersect_key($docs, array_flip($colsDocs));
            unset($payload['id'], $payload['created_at'], $payload['updated_at'], $payload['ubicacion_id']);
            $ubic->documentos()->updateOrCreate(['ubicacion_id' => $ubic->id], $payload + ['ubicacion_id' => $ubic->id]);

            // 7.3) Rubros (principal + anexos) y rubro_id directo
            $ubic->rubros()->sync(array_values(array_unique(array_merge([$principal], $anexos))));
            $ubic->rubro_id = $principal ?: null;
            $ubic->save();

            // 7.4) Teléfonos
            foreach ($telSan as $t) { $ubic->telefonos()->create(['telefono'=>$t]); }

            // 7.5) Disposiciones / Habilitaciones
            foreach (($this->state['disposiciones'] ?? []) as $d) {
                $num = trim((string)($d['numero'] ?? '')); if ($num==='') continue;
                $ubic->disposiciones()->create(['numero'=>$num,'fecha'=>!empty($d['fecha'])?$d['fecha']:null]);
            }
            foreach (($this->state['habilitaciones'] ?? []) as $h) {
                $num = trim((string)($h['numero'] ?? '')); if ($num==='') continue;
                $ubic->habilitaciones()->create(['numero'=>$num,'fecha'=>!empty($h['fecha'])?$h['fecha']:null]);
            }

            $this->registrarHistorialEstado(
                $ubic,
                $estadoBase,
                $estadoLabel,
                $this->state['cambio_tipo'] ?? null,
                $this->state['fecha_alta'] ?? null,
                $this->state['fecha_baja'] ?? null,
                $this->state['fecha_vto']  ?? null
            );

            try {
                $ubic->movimientos()->create([
                    'etapa'   => 'estado',
                    'detalle' => $estadoLabel,
                ]);
            } catch (\Throwable $e) { }
        });

        // --- 8) UI: reset y cerrar ---
        $this->resetPage();
        $this->reset('state','ubicacion');
        $this->dispatch('hide-form', ['message' => 'Comercio creado correctamente.']);
    }


    public function updateComercio()
    {

        // 1) Validar
        $rules = $this->reglasComunes(true);
        $validated = Validator::make($this->state, $rules)->validate();

        // 2) Normalizaciones de strings y números
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $c) {
            if (!empty($validated[$c] ?? null)) $validated[$c] = Str::title($validated[$c]);
        }
        $validated['dni_cuit'] = preg_replace('/\D/','', $validated['dni_cuit'] ?? '');
        if (($validated['persona_tipo'] ?? 'fisica') === 'juridica') {
            $validated['apellido'] = null; $validated['nombres'] = null;
        }
        if (array_key_exists('monto_pagar', $validated)) {
            $validated['monto_pagar'] = $this->normalizeDecimal($validated['monto_pagar']);
        }

        // 3) MAPEO de estado (base → canon) + label + situacion
        $rawEstado   = $this->state['estado'] ?? $this->ubicacion->estado ?? 'entramite';
        $estadoBase  = $this->estadoBaseNormalize($rawEstado);          // '021' / '032' / 'baja' / ...
        $estadoCanon = $this->mapBaseToCanon($estadoBase);              // 'entramite' / 'irregular' / ...
        $cambioKey = trim((string)($this->state['cambio_tipo'] ?? ''));
        if (($cambioKey === '' || $cambioKey === null) && in_array($estadoBase, ['021','032'], true)) {
            $cambioKey = $this->inferCambioKeyFromEstado(
                $this->ubicacion->estado_label ?? $this->ubicacion->estado ?? '',
                $estadoBase
            );
        }
        $estadoLabel = $this->buildEstadoLabel($estadoBase, $cambioKey);
        $tmpState = $this->state;
        $tmpState['estado'] = $estadoCanon;
        $rules = $this->reglasComunes(true);
        \Validator::make(['state'=>$tmpState], $rules)->validate();

        $validated['estado']       = $estadoCanon;
        $validated['estado_base']  = $estadoBase;
        $validated['estado_label'] = $estadoLabel;
        $validated['situacion']    = $this->calcularSituacion($estadoCanon, (bool)($this->state['es_clausurado'] ?? false));

        // 4) Limpiar campos que no van directo a ubicaciones
        unset($validated['documentos'], $validated['domicilio_responsable'], $validated['nomenclatura'], $validated['es_clausurado']);

        // 5) Re-enriquecer geodatos si cambió la dirección
        $dirVieja = trim((string)$this->ubicacion->getOriginal('domicilio_comercio'));
        $dirNueva = trim((string)($validated['domicilio_comercio'] ?? ''));
        if ($dirNueva !== '' && $dirNueva !== $dirVieja) {
            $enricher = app(\App\Services\UbicacionGeoEnricher::class);
            $validated = $enricher->enrich($validated);
        }

        // 6) Filtrar columnas reales
        $colsUbic = Schema::getColumnListing('ubicaciones');
        $dataUbic = array_intersect_key($validated, array_flip($colsUbic));

        // 7) Datos auxiliares para tablas relacionadas
        $docsFromUI = $this->state['documentos'] ?? [];
        $principal  = (int)($this->state['rubro_id'] ?? 0);
        $anexos     = collect($this->state['rubros_anexos'] ?? [])
                        ->map(fn($v)=>(int)$v)->filter()->reject(fn($id)=>$id === $principal)->unique()->values()->all();

        $tels = collect($this->state['telefonos'] ?? [])
                    ->map(fn($t)=>trim((string)$t))->filter(fn($t)=>$t!=='')->unique()->values();

        // 8) Persistir todo en TX
        DB::transaction(function () use ($dataUbic, $docsFromUI, $principal, $anexos, $tels, $estadoBase, $estadoLabel) {
            if (array_key_exists('estado', $dataUbic)) {
                $dataUbic['estado'] = $this->mapBaseToCanon(
                    $this->estadoBaseNormalize((string)$dataUbic['estado'])
                );
            }

            $this->ubicacion->update($dataUbic);

            // Historial de estados (siempre que guardamos)
            $this->registrarHistorialEstado(
                $this->ubicacion,
                $estadoBase,
                $estadoLabel,
                $this->state['cambio_tipo'] ?? null,
                $this->state['fecha_alta'] ?? null,
                $this->state['fecha_baja'] ?? null,
                $this->state['fecha_vto']  ?? null
            );

            // Documentos
            $docs = $this->normalizeDocsArray($docsFromUI);
            $tipoUso = $docs['doc_uso_inmueble_tipo'] ?? null;
            foreach (['doc_uso_boleto','doc_uso_contrato','doc_uso_comodato','doc_uso_titulo','doc_uso_cert_ocupacion'] as $k) { $docs[$k] = false; }
            $mapUso = ['boleto'=>'doc_uso_boleto','contrato'=>'doc_uso_contrato','comodato'=>'doc_uso_comodato','titulo'=>'doc_uso_titulo','cert_ocupacion'=>'doc_uso_cert_ocupacion'];
            if ($tipoUso && isset($mapUso[$tipoUso])) $docs[$mapUso[$tipoUso]] = true;

            $actualBD = $this->ubicacion->documentos?->toArray() ?? [];
            $colsDoc  = Schema::getColumnListing('ubicacion_documentos');
            $payload  = array_merge(
                array_intersect_key($actualBD, array_flip($colsDoc)),
                array_intersect_key($docs,     array_flip($colsDoc))
            );
            $payload['ubicacion_id'] = $this->ubicacion->id;

            $this->ubicacion->documentos()->updateOrCreate(
                ['ubicacion_id' => $this->ubicacion->id],
                $payload
            );

            // Rubros
            $this->ubicacion->rubros()->sync(array_values(array_unique(array_merge([$principal], $anexos))));
            $this->ubicacion->rubro_id = $principal ?: null;
            $this->ubicacion->save();

            // Teléfonos
            $this->ubicacion->telefonos()->delete();
            foreach ($tels as $t) $this->ubicacion->telefonos()->create(['telefono'=>$t]);

            // Disposiciones (desde los helpers de tu form)
            $nd = trim((string)($this->state['numero_disposicion'] ?? ''));
            $nh = trim((string)($this->state['numero_habilitacion'] ?? ''));
            if (!empty($nd)) {
                if (empty($this->state['disposiciones']) || !is_array($this->state['disposiciones'])) {
                    $this->state['disposiciones'] = [['numero'=>$nd, 'fecha'=>null]];
                } else {
                    $this->state['disposiciones'][0]['numero'] = $nd;
                }
            }
            if (!empty($nh)) {
                if (empty($this->state['habilitaciones']) || !is_array($this->state['habilitaciones'])) {
                    $this->state['habilitaciones'] = [['numero'=>$nh, 'fecha'=>null]];
                } else {
                    $this->state['habilitaciones'][0]['numero'] = $nh;
                }
            }

            $this->ubicacion->disposiciones()->delete();
            foreach (($this->state['disposiciones'] ?? []) as $d) {
                $num = trim((string)($d['numero'] ?? '')); if ($num==='') continue;
                $this->ubicacion->disposiciones()->create(['numero'=>$num,'fecha'=>!empty($d['fecha'])?$d['fecha']:null]);
            }

            $this->ubicacion->habilitaciones()->delete();
            foreach (($this->state['habilitaciones'] ?? []) as $h) {
                $num = trim((string)($h['numero'] ?? '')); if ($num==='') continue;
                $this->ubicacion->habilitaciones()->create(['numero'=>$num,'fecha'=>!empty($h['fecha'])?$h['fecha']:null]);
            }
        });

        // 9) UI
        $this->dispatch('ubicacion-actualizada', id: $this->ubicacion->id);
        $this->resetPage();
        $this->dispatch('hide-form', ['message' => 'Registro actualizado correctamente']);
    }

        // === Inserta en tu tabla ubicacion_estado_historial ===
    private function registrarHistorialEstado(
        Ubicacion|int|null $ubic,   // acepta modelo, id o null
        string $estadoBase,
        string $estadoLabel,
        ?string $fechaAlta = null,
        ?string $fechaBaja = null,
        ?string $fechaVto  = null
    ): void {
        // Resolver ID de la ubicación
        $ubicacionId = $ubic instanceof Ubicacion
            ? $ubic->id
            : (is_numeric($ubic) ? (int)$ubic : null);

        if (!$ubicacionId) {
            // nada que hacer si no hay ID
            Log::warning('registrarHistorialEstado: ubicacionId vacío', [
                'estado_base' => $estadoBase,
                'estado_label'=> $estadoLabel,
            ]);
            return;
        }

        // Normalizar fechas a 'Y-m-d' si vienen como Carbon/DateTime
        $fmt = fn($v) => $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : ($v ?: null);

        try {
            UbicacionEstadoHist::create([
                'ubicacion_id' => $ubicacionId,
                'estado_base'  => $estadoBase,
                'estado_label' => $estadoLabel,
                'fecha_alta'   => $fmt($fechaAlta),
                'fecha_baja'   => $fmt($fechaBaja),
                'fecha_vto'    => $fmt($fechaVto),
                'user_id'      => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error guardando historial de estado', [
                'msg' => $e->getMessage(),
            ]);
            // no rompas la UX
        }
    }

    public function movimientos()
    {
        return $this->hasMany(\App\Models\UbicacionEstadoHist::class, 'ubicacion_id')->latest('id');
    }

    private function parseCambioDesdeEstado(string $estadoRaw): array
    {
        $raw = trim($estadoRaw);
        $sl  = mb_strtolower($raw);

        // Detecto base
        if (str_starts_with($sl, '021'))       { $base = '021'; }
        elseif (str_starts_with($sl, '032'))   { $base = '032'; }
        elseif (in_array($sl, ['entramite','en tramite','en trámite','en_tramite','en-tramite'])) { $base = '021'; }
        elseif ($sl === 'irregular')           { $base = '032'; }
        elseif (in_array($sl, ['baja','baja de oficio','baja_oficio','baja-oficio','expediente sin efecto','sin_efecto','exp_sin_efecto'])) {
            return ['base' => $sl, 'cambio_key' => null]; // no aplica cambios
        } else { $base = '021'; }

        // Extraer etiqueta a la derecha del guion, si existe
        $label = '';
        if (str_contains($raw, '-')) {
            $label = trim(explode('-', $raw, 2)[1] ?? '');
        }
        if ($label === '') return ['base' => $base, 'cambio_key' => null];

        // Mapear etiqueta → key (case-insensitive)
        $opts = $this->cambiosOptions($base);
        $buscado = mb_strtolower($label);
        foreach ($opts as $key => $txt) {
            if (mb_strtolower($txt) === $buscado) {
                return ['base' => $base, 'cambio_key' => $key];
            }
        }
        return ['base' => $base, 'cambio_key' => null];
    }


    /** ======== Otros ======== */
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
        $base = $this->normalizarEstado($this->state['estado'] ?? null);

        $reglas = [
            'state.fecha_alta' => 'nullable|date',
            'state.fecha_baja' => 'nullable|date',
            'state.fecha_vto'  => 'nullable|date',
        ];

        switch ($base) {
            case '021':
                // Ahora 021 requiere ALTA + VTO
                $reglas['state.fecha_alta'] = 'required|date';
                $reglas['state.fecha_vto']  = 'required|date|after_or_equal:state.fecha_alta';
                $reglas['state.fecha_baja'] = 'nullable';
                break;

            case '032':
                // 032 requiere ALTA; VTO opcional (pero coherente si se carga)
                $reglas['state.fecha_alta'] = 'required|date';
                $reglas['state.fecha_vto']  = 'nullable|date|after_or_equal:state.fecha_alta';
                $reglas['state.fecha_baja'] = 'nullable';
                break;

            case '040':
                // 040 requiere ALTA; VTO opcional (si querés obligatorio, cambiá a required)
                $reglas['state.fecha_alta'] = 'required|date';
                $reglas['state.fecha_vto']  = 'nullable|date|after_or_equal:state.fecha_alta';
                $reglas['state.fecha_baja'] = 'nullable';
                break;

            case 'baja':
            case 'baja_oficio':
            case 'exp_sin_efecto':
                // Requiere ALTA y BAJA. BAJA >= ALTA y ambas no futuras.
                $tieneAltaAntes = !empty($this->ubicacion?->fecha_alta) || !empty($this->state['fecha_alta']);

                // Alta requerida (si no estaba previamente)
                $reglas['state.fecha_alta'] = ($tieneAltaAntes ? 'nullable' : 'required') . '|date|before_or_equal:today';

                // Baja requerida, posterior/igual a alta y no futura
                $reglas['state.fecha_baja'] = 'required|date'
                    . ($tieneAltaAntes ? '|after_or_equal:state.fecha_alta' : '')
                    . '|before_or_equal:today';

                // VTO no aplica
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
