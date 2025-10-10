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
use App\Support\HandlesEstados;
use Illuminate\Support\Str;

class ComercioMapa extends AdminComponent
{
    use HandlesEstados;

    public array $barrios = [];
    public array $estados = [
        'entramite'   => '021/90',
        'irregular'   => '032/01',
        '040'         => '040/25',
        'baja'        => 'Baja',
        'baja_oficio' => 'Baja de Oficio',
        'sin_efecto'  => 'Expediente sin Efecto',
    ];

    public string $fantasiaQuery = '';
    public array $fantasiaSuggestions = [];

    public array $rubroOpts = [];
    public ?int $selectedRubroId = null;

    public array $nomenOpts = [];
    public string $selectedNomen = '';

    public string $selectedBarrio = '';
    public string $selectedEstado = '';

    public bool $solo_clausurados = false;

    public array $ubicaciones = [];
    public $state = ['tipo_hab' => 'prev', 'documentos' => []];
    public $showEditModal = false;
    public string $formKey = '';

    public array $anexoOpts = [];

    protected array $docLabels = [
        // General
        'doc_libre_deuda_municipal' => 'Certificado de libre deuda municipal',
        'doc_planeamiento_urbano'   => 'Dirección de Planeamiento Urbano',
        'doc_solicitud_habilitacion_pago' => 'Solicitud de habilitación + pago',
        'doc_comprobante_uso_local' => 'Comprobante de uso del local',
        'doc_afip_constancia'       => 'Constancia de inscripción AFIP',
        'doc_recaudacion_rn'        => 'Constancia de inscripción Agencia Recaudación RN',
        'doc_fotocopia_dni'         => 'Fotocopia de DNI',
        'doc_comprobante_uso_inmueble'    => 'Comprobante de uso del inmueble',
        'doc_libre_deuda_tasas_inmueble'  => 'Libre deuda de tasas del inmueble',
        'doc_aptitud_tecnica_local' => 'Certificado de aptitud técnica',
        'doc_cocap_rhi'             => 'Certificado CO.CA.P.RHI',
        'doc_nota_carteleria_obras' => 'Nota a Obras por cartelería',
        'doc_libro_actas_100'       => 'Libro de actas (100 hojas)',
        // Jurídica
        'doc_acta_constitucion'     => 'Acta de constitución',
        'doc_contrato_societario'   => 'Contrato societario',
        'doc_docs_representantes'   => 'Documentación de representantes',

        // Extras de “irregular”
        'doc_cert_electricidad' => 'Certificado de electricidad',
        'doc_cert_gasista'      => 'Certificado de gasista',
        'doc_inf_seg_hig'       => 'Informe de seguridad e higiene',
        'doc_protocolo_mput'    => 'Protocolo de puesta a tierra',
        'doc_carga_fuego'       => 'Carga de fuego',
        'doc_inf_ascensores'    => 'Informe de ascensores',
        'doc_poliza_seguro'     => 'Póliza de seguro',
        'doc_cert_cocapri'      => 'Certificado CO.CA.P.R.I',
        'doc_inf_splif'         => 'Informe del SPLIF',
        'doc_control_plagas'    => 'Control de plagas',
        'doc_cert_caldera'      => 'Certificado de caldera',
        'doc_cert_zavecom'      => 'Certificado ZAVECOM',
        'doc_cert_salud_prov'   => 'Certificado de salud (Provincia)',

        // Otros
        'doc_manipulacion_alimentos'=> 'Certificado de manipulación de alimentos',
        'doc_nota_baja'             => 'Nota de baja',
        'doc_pago_baja'             => 'Pago de baja',
        'doc_acta_inspeccion'       => 'Acta de inspección',
    ];


    protected array $docKeysGeneral = [
        'doc_libre_deuda_municipal','doc_planeamiento_urbano','doc_solicitud_habilitacion_pago',
        'doc_comprobante_uso_local','doc_afip_constancia','doc_recaudacion_rn','doc_fotocopia_dni',
        'doc_comprobante_uso_inmueble','doc_libre_deuda_tasas_inmueble','doc_aptitud_tecnica_local',
        'doc_cocap_rhi','doc_nota_carteleria_obras','doc_libro_actas_100',
    ];
    protected array $docKeysJuridica = ['doc_acta_constitucion','doc_contrato_societario','doc_docs_representantes'];
    protected array $docDefaults = [];

    #[On('open-create-from-map')]
    public function openCreateFromMap($payload = null): void
    {
        $isCoord = fn($v) => is_string($v) && preg_match('/^\s*-?\d+(\.\d+)?\s*$/', $v);
        $looksLikeAddress = function($s) {
            if (!is_string($s)) return false;
            $s = mb_strtolower(trim($s));
            
            if (preg_match('/\d/', $s) && str_contains($s, ' ')) return true;
            
            foreach (['calle','av','avenida','ruta','pasaje','barrio'] as $w) {
                if (str_contains($s, $w)) return true;
            }
            return false;
        };
        $looksLikeNomen = function($s) {
            if (!is_string($s)) return false;
            $s = trim($s);
            
            return (bool) preg_match('/^[A-Za-z0-9\-\/\s]{4,}$/', $s) && !$isCoord($s);
        };

        $lat = $lng = null;
        $direccion = $barrio = $nomen = null;

        if (is_array($payload)) {
            $lat       = $payload['lat']       ?? null;
            $lng       = $payload['lng']       ?? null;
            $direccion = $payload['direccion'] ?? null;
            $barrio    = $payload['barrio']    ?? null;
            $nomen     = $payload['nomen']     ?? ($payload['nomenclatura'] ?? null);
        } else {
            $args = func_get_args();
            // forma A: (lat,lng,direccion,barrio,nomen)
            if (count($args) >= 5 && is_numeric($args[0]) && is_numeric($args[1])) {
                [$lat,$lng,$direccion,$barrio,$nomen] = [$args[0],$args[1],$args[2],$args[3],$args[4]];
            }
            // forma B: (direccion,barrio,nomen)  o  (nomen,barrio,direccion)  ← aquí nos podemos confundir
            elseif (count($args) >= 3) {
                [$a,$b,$c] = [$args[0],$args[1],$args[2]];
                // Si el tercero parece dirección y el primero parece nomenclatura, los swapeamos
                if ($looksLikeAddress($c) && $looksLikeNomen($a)) {
                    $direccion = (string)$c;
                    $barrio    = (string)$b;
                    $nomen     = (string)$a;
                } else {
                    $direccion = (string)$a;
                    $barrio    = (string)$b;
                    $nomen     = (string)$c;
                }
            }
        }

        $lat = isset($lat) && $lat !== '' ? (float)$lat : null;
        $lng = isset($lng) && $lng !== '' ? (float)$lng : null;

        $direccion = is_string($direccion) ? trim($direccion) : '';
        $barrio    = is_string($barrio)    ? trim($barrio)    : '';
        $nomen     = is_string($nomen)     ? trim($nomen)     : null;

        // Evitar que vuele una coordenada a nomenclatura
        if ($nomen !== null && $isCoord($nomen)) {
            $nomen = null;
        }

        // --- state inicial del form ---
        $this->state = [
            'persona_tipo'       => 'fisica',
            'tipo_hab'           => 'prev',
            'estado'             => '021',
            'fecha_alta'         => null,
            'fecha_baja'         => null,
            'fecha_vto'          => null,
            'rubro_id'           => null,
            'dni_cuit'           => '',
            'apellido'           => '',
            'nombres'            => '',
            'razon_social'       => '',
            'nombre_comercial'   => '',
            'lat'                => $lat,
            'lng'                => $lng,
            'domicilio_comercio' => $direccion,      // <- sólo dirección
            'barrio'             => $barrio,
            'nomenclatura'       => $nomen ?? '',    // <- nomen saneada
            'correo'             => '',
            'telefono'           => '',
            'monto_pagar'        => null,
            'observaciones'      => '',
            'cambio_tipo'        => '',
            'telefonos'          => [''],
            'rubros_anexos'      => [],
            'disposiciones'      => [['numero'=>'','fecha'=>null]],
            'habilitaciones'     => [['numero'=>'','fecha'=>null]],
            'documentos'         => $this->docDefaults ?? [],
        ];

        $this->formKey = (string) \Illuminate\Support\Str::uuid();
        $this->showEditModal = false;

        $this->dispatch('show-form',
            rubroId: ($this->state['rubro_id'] ?? null),
            anexos:  ($this->state['rubros_anexos'] ?? [])
        );
        $this->dispatch('refresh-selects',
            rubroId: ($this->state['rubro_id'] ?? null),
            anexos:  ($this->state['rubros_anexos'] ?? [])
        );
    }

    private function docKeysForEstado(string $estadoBase, bool $esJuridica): array
    {
        $baseGeneral = $this->docKeysGeneral;
        $juridica    = $esJuridica ? $this->docKeysJuridica : [];

        return match ($estadoBase) {
            '021','entramite' => array_values(array_unique(array_merge(
                array_diff($baseGeneral, [
                    'doc_nota_carteleria_obras',
                    'doc_planeamiento_urbano',
                    'doc_comprobante_uso_local'
                ]),
                ['doc_manipulacion_alimentos'],
                $juridica
            ))),
            '032','irregular' => [
                'doc_cert_electricidad','doc_cert_gasista','doc_inf_seg_hig','doc_protocolo_mput','doc_carga_fuego',
                'doc_inf_ascensores','doc_poliza_seguro','doc_cert_cocapri','doc_inf_splif','doc_control_plagas',
                'doc_cert_caldera','doc_cert_zavecom','doc_cert_salud_prov','doc_comprobante_uso_inmueble',
            ],
            'baja','baja_oficio','exp_sin_efecto' => [
                'doc_pago_baja','doc_libre_deuda_municipal','doc_acta_inspeccion','doc_nota_baja',
            ],
            '040' => [],
            default => array_merge($baseGeneral, $juridica),
        };
    }

    public function getDocSchemaProperty(): array
    {
        // usar base normalizada como en el otro componente
        $estadoBase = $this->estadoBaseNormalize($this->state['estado'] ?? '021'); // default 021
        $esJuridica = ($this->state['persona_tipo'] ?? 'fisica') === 'juridica';

        $keys = $this->docKeysForEstado($estadoBase, $esJuridica);

        $items = [];
        foreach ($keys as $k) {
            $items[] = ['key' => $k, 'label' => $this->docLabels[$k] ?? $k, 'type' => 'checkbox'];
        }

        $showUsoInmueble = in_array('doc_comprobante_uso_inmueble', $keys, true) || $estadoBase === '021';

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

        // Unificación AFIP (legacy → actual)
        $out['doc_afip_constancia'] =
            (bool)($out['doc_afip_constancia'] ?? false)
         || (bool)($out['doc_afip_constancia_fisica'] ?? false)
         || (bool)($out['doc_afip_constancia_juridica'] ?? false);

        return $out;
    }

    public function crearDesdeMapaConDatos(
        ?string $direccion = null,
        ?string $barrio = null,
        ?string $nomen = null,
        $lat = null,
        $lng = null
    ): void {
        // Helpers de saneo
        $isCoord = function ($v) {
            if ($v === null) return false;
            $s = trim((string)$v);
            return $s !== '' && preg_match('/^-?\d+(\.\d+)?$/', $s);
        };

        // Normalizaciones básicas
        $direccion = is_string($direccion) ? trim($direccion) : '';
        $barrio    = is_string($barrio) ? trim($barrio) : '';
        $nomen     = is_string($nomen) ? trim($nomen) : '';

        // Evitar colar coordenadas como nomenclatura
        if ($nomen !== '' && $isCoord($nomen)) {
            $nomen = '';
        }

        $lat = ($lat === '' || $lat === null) ? null : (float)$lat;
        $lng = ($lng === '' || $lng === null) ? null : (float)$lng;

        // Estado inicial del form (alineado con Ubicaciones/ComercioMapa)
        $this->state = [
            'persona_tipo'       => 'fisica',
            'tipo_hab'           => 'prev',
            'estado'             => null,
            'fecha_alta'         => null,
            'fecha_baja'         => null,
            'fecha_vto'          => null,
            'rubro_id'           => null,
            'dni_cuit'           => '',
            'apellido'           => '',
            'nombres'            => '',
            'razon_social'       => '',
            'nombre_comercial'   => '',
            'lat'                => $lat,
            'lng'                => $lng,
            'domicilio_comercio' => $direccion,   // <- dirección
            'barrio'             => $barrio,
            'nomenclatura'       => $nomen,       // <- nomenclatura saneada
            'correo'             => '',
            'cambio_tipo'        => '',
            'telefono'           => '',
            'monto_pagar'        => null,
            'observaciones'      => '',
            'telefonos'          => [''],
            'rubros_anexos'      => [],
            'disposiciones'      => [['numero'=>'','fecha'=>null]],
            'habilitaciones'     => [['numero'=>'','fecha'=>null]],
            'documentos'         => $this->docDefaults ?? [], // usa tus defaults
        ];

        $this->formKey = (string) \Illuminate\Support\Str::uuid();
        $this->showEditModal = false;

        // Abrir el formulario con selects preparados
        $this->dispatch('show-form',
            rubroId: ($this->state['rubro_id'] ?? null),
            anexos:  ($this->state['rubros_anexos'] ?? [])
        );
        $this->dispatch('refresh-selects',
            rubroId: ($this->state['rubro_id'] ?? null),
            anexos:  ($this->state['rubros_anexos'] ?? [])
        );
    }

    // Alias 2: por si lo emiten como evento con otro nombre
    #[On('crear-desde-mapa')]
    public function crearDesdeMapa($payload): void
    {
        $this->openCreateFromMap($payload);
    }

    public function mount(GeoService $geo)
    {
        abort_unless(Gate::allows('view-maps'), 403);

        $this->barrios   = $geo->barriosList();
        $this->rubroOpts = Rubro::orderBy('subrubro')->get(['id','subrubro'])->toArray();
        $this->nomenOpts = $this->leerNomenclaturas();

        $this->emitUbicaciones();

        $this->anexoOpts = $this->rubroOpts;
        $this->docDefaults = array_fill_keys(array_merge($this->docKeysGeneral, $this->docKeysJuridica), false);
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

    // ===== rubro / barrio / estado / nomen / claus
    public function updatedSelectedRubroId() { $this->emitUbicaciones(); }
    public function updatedSelectedBarrio()  { $this->emitUbicaciones(); }
    public function updatedSelectedEstado()  { $this->emitUbicaciones(); }
    public function updatedSelectedNomen()   { $this->emitUbicaciones(); }
    public function updatedSoloClausurados() { $this->emitUbicaciones(); } // ✅

    private function queryUbicaciones()
    {
        $subId    = $this->selectedRubroId ?: null;
        $fantasia = trim($this->fantasiaQuery ?? '');

        return Ubicacion::with('rubro:id,mega_rubro,rubro_madre,subrubro')
            ->when($subId, fn($q)=> $q->where('rubro_id', $subId))
            ->when($this->selectedBarrio !== '', fn($q)=> $q->where('barrio', $this->selectedBarrio))
            ->when($this->selectedEstado !== '', fn($q)=> $q->where('estado', $this->selectedEstado))
            ->when($this->solo_clausurados, fn($q)=> $q->where('situacion', 'clausurado'))
            ->when($fantasia !== '', function($q) use ($fantasia) {
                $t = '%'.$fantasia.'%';
                $q->where('nombre_comercial','like',$t);
            })
            ->orderByRaw("COALESCE(NULLIF(nombre_comercial,''), razon_social) asc")
            ->get([
                'id','razon_social','nombre_comercial','domicilio_comercio',
                'lat','lng','rubro_id','barrio','estado','situacion', // ✅ incluir situacion
                \DB::raw('nomenclatura as nomen'),
            ])
            ->map(function ($u) {
                return [
                    'id'                => $u->id,
                    'razon_social'      => $u->razon_social,
                    'nombre_comercial'  => $u->nombre_comercial,
                    'domicilio_comercio'=> $u->domicilio_comercio,
                    'lat'               => $u->lat,
                    'lng'               => $u->lng,
                    'barrio'            => $u->barrio,
                    'estado'            => $u->estado,
                    'situacion'         => $u->situacion, // ✅ para badge en popup
                    'nomen'             => $u->nomen,
                    'rubro'             => [
                        'id'       => $u->rubro?->id,
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

    // ===================== abrir modal NUEVO =====================
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
            'lat'                  => null,
            'lng'                  => null,
            'correo'               => '',
            'telefono'             => '',
            'nomenclatura'         => '',
            'numero_disposicion'   => '',
            'numero_habilitacion'  => '',
            'monto_pagar'          => null,
            'observaciones'        => '',
            'cambio_tipo'          => '',
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

        $this->dispatch('refresh-selects',
            rubroId: ($this->state['rubro_id'] ?? null),
            anexos:  ($this->state['rubros_anexos'] ?? [])
        );
    }

    // ===================== abrir modal desde mapa =====================
    public function prefillAndOpenForm(?string $direccion = null, ?string $barrio = null, ?string $nomen = null): void
    {
        $this->nuevoComercio();
        if ($direccion) $this->state['domicilio_comercio'] = $direccion;
        if ($nomen)     $this->state['nomenclatura']      = $nomen;

        $this->dispatch('show-form',
            rubroId: ($this->state['rubro_id'] ?? null),
            anexos:  ($this->state['rubros_anexos'] ?? [])
        );

        $this->dispatch('refresh-selects',
            rubroId: ($this->state['rubro_id'] ?? null),
            anexos:  ($this->state['rubros_anexos'] ?? [])
        );
    }

    // ===================== validaciones/aux =====================
    private function normalizarEstado(?string $estado): string
    {
        $e = trim(mb_strtolower($estado ?? ''));
        return match ($e) {
            'en tramite','en trámite','en_tramite','en-tramite' => 'entramite',
            'vigente' => 'vigente',
            'irregular' => 'irregular',
            '040'         => '040',
            'baja' => 'baja',
            'baja_oficio'=> 'baja_oficio',
            'sin_efecto' => 'sin_efecto',
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
            case 'vigente':
                if ($esCreate) $reglas['state.fecha_alta'] = 'required|date';
                break;
            case 'irregular':
                $reglas['state.fecha_alta'] = 'required|date';
                break;
            case '040': 
                $reglas['state.fecha_alta'] = 'required|date';
                $reglas['state.fecha_vto']  = 'nullable|date|after_or_equal:state.fecha_alta';
                break;
            case 'baja':
            case 'baja_oficio':
            case 'sin_efecto':
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

    protected function reglasComunes(bool $isUpdate = false): array
    {
        return [
            'state.persona_tipo' => ['required', Rule::in(['fisica','juridica'])],
            'state.dni_cuit'     => ['bail','required','string','regex:/^\d{7,8}$|^\d{2}-\d{7,8}-\d{1}$|^\d{11}$/'],
            'state.rubro_id'     => ['required','exists:rubros,id'],

            // Acepta canónicos y bases, incluyendo 040
            'state.estado'       => ['required', Rule::in([
                'entramite','irregular','baja','baja_oficio','sin_efecto','040',
                '021','032','040','exp_sin_efecto',
            ])],
            'state.tipo_hab'     => ['required', Rule::in(['definitiva','prev'])],

            'state.fecha_alta'   => ['nullable','date'],
            'state.fecha_baja'   => ['nullable','date'],
            'state.fecha_vto'    => ['nullable','date'],

            'state.nombre_comercial' => ['nullable','string','min:2','max:120'],
            'state.apellido'         => ['nullable','string','min:2','max:60'],
            'state.nombres'          => ['nullable','string','min:2','max:80'],
            'state.razon_social'     => ['nullable','string','min:2','max:120'],
            'state.domicilio_comercio'=> ['nullable','string','min:3','max:160'],
            'state.correo'           => ['nullable','email:rfc,dns','max:120'],
            'state.telefono'         => ['nullable','regex:/^[\d\s()+\-]{6,20}$/'],
            'state.nomenclatura'     => ['nullable','string','max:80'],
            'state.observaciones'    => ['nullable','string','max:500'],
            'state.documentos'       => ['array'],
            'state.es_clausurado'    => ['boolean'],
        ] + $this->reglasPorTipoPersona();
    }

    protected function reglasPorTipoPersona(): array
    {
        // Si es física: apellido + nombres obligatorios. Si es jurídica: razón social obligatoria.
        if (($this->state['persona_tipo'] ?? 'fisica') === 'fisica') {
            return [
                'state.apellido' => ['required','string','min:2','max:60'],
                'state.nombres'  => ['required','string','min:2','max:80'],
            ];
        }
        return [
            'state.razon_social' => ['required','string','min:2','max:120'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'state.persona_tipo.required'  => 'Seleccioná el tipo de persona.',
            'state.dni_cuit.required'      => 'Ingresá DNI o CUIT.',
            'state.rubro_id.required'      => 'Seleccioná el subrubro.',
            'state.estado.required'        => 'Seleccioná el estado.',
            'state.monto_pagar.regex'      => 'Usá hasta 2 decimales (ej: 123.45).',
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

    // ===================== CREAR =====================
    public function createCliente()
    {
        // --- 0) Mapear estado del form → base/canónico/label
        $rawEstado   = $this->state['estado'] ?? 'entramite';      // puede venir '021'/'032' o canónico
        $estadoBase  = $this->estadoBaseNormalize($rawEstado);     // '021' | '032' | 'baja' | 'baja_oficio' | 'exp_sin_efecto'
        $estadoCanon = $this->mapBaseToCanon($estadoBase);         // 'entramite' | 'irregular' | 'baja' | 'baja_oficio' | 'sin_efecto'
        $cambioKey   = (string)($this->state['cambio_tipo'] ?? '');
        $estadoLabel = $this->buildEstadoLabel($estadoBase, $cambioKey);

        // Ponemos el canónico en state para que reglas dependientes funcionen
        $this->state['estado'] = $estadoCanon;

        // --- 1) Reglas y validación
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

        $validated = $this->validate($reglas, $this->mensajes(), $this->atributos());
        $data = $validated['state'];

        // --- 2) Normalizaciones
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $c) {
            if (!empty($data[$c] ?? null)) $data[$c] = \Illuminate\Support\Str::title($data[$c]);
        }
        $data['dni_cuit'] = preg_replace('/\D/','', $data['dni_cuit'] ?? '');
        if (($data['persona_tipo'] ?? 'fisica') === 'juridica') {
            $data['apellido'] = null; $data['nombres'] = null;
        }

        // --- 3) Estado/Situación correctos para persistir
        $data['estado']       = $estadoCanon;         // FK válida (canónica)
        $data['estado_base']  = $estadoBase;          // '021'/'032'/...
        $data['estado_label'] = $estadoLabel;         // "021 - ..."
        $data['situacion']    = $this->calcularSituacion($estadoCanon, (bool)($this->state['es_clausurado'] ?? false));

        // Helpers de número sueltos → primer item del repeater
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

        // Limpiar campos no directos
        $docsFromUI = $data['documentos'] ?? [];
        unset($data['documentos'], $data['domicilio_responsable'], $data['es_clausurado']);

        // Enriquecer geodatos si hay dirección
        if (!empty(trim((string)($data['domicilio_comercio'] ?? '')))) {
            $enricher = app(\App\Services\UbicacionGeoEnricher::class);
            $data = $enricher->enrich($data);
        }

        // Filtrar a columnas reales
        $colsUbic = \Illuminate\Support\Facades\Schema::getColumnListing('ubicaciones');
        $data     = array_intersect_key($data, array_flip($colsUbic));

        // Preparar relaciones
        $principal  = (int)($this->state['rubro_id'] ?? 0);
        $anexos     = collect($this->state['rubros_anexos'] ?? [])
                        ->map(fn($v)=>(int)$v)->filter()
                        ->reject(fn($id)=>$id === $principal)->unique()->values()->all();
        $tels = collect($this->state['telefonos'] ?? [])
                    ->map(fn($t)=>trim((string)$t))->filter(fn($t)=>$t!=='')->unique()->values();

        // --- 4) Persistencia en TX
        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $estadoBase, $estadoLabel, $docsFromUI, $principal, $anexos, $tels) {
            // 4.1) Ubicación
            $ubic = \App\Models\Ubicacion::create($data);

            // 4.2) Documentos (normalizar + uso inmueble exclusivo)
            $docs = $this->normalizeDocsArray($docsFromUI);
            $tipo = $docs['doc_uso_inmueble_tipo'] ?? null;
            foreach (['doc_uso_boleto','doc_uso_contrato','doc_uso_comodato','doc_uso_titulo','doc_uso_cert_ocupacion'] as $k) {
                $docs[$k] = false;
            }
            $mapUso = [
                'boleto'         => 'doc_uso_boleto',
                'contrato'       => 'doc_uso_contrato',
                'comodato'       => 'doc_uso_comodato',
                'titulo'         => 'doc_uso_titulo',
                'cert_ocupacion' => 'doc_uso_cert_ocupacion',
            ];
            if ($tipo && isset($mapUso[$tipo])) $docs[$mapUso[$tipo]] = true;

            $colsDocs = \Illuminate\Support\Facades\Schema::getColumnListing('ubicacion_documentos');
            $payload  = array_intersect_key($docs, array_flip($colsDocs));
            unset($payload['id'], $payload['created_at'], $payload['updated_at'], $payload['ubicacion_id']);
            $ubic->documentos()->updateOrCreate(['ubicacion_id' => $ubic->id], $payload + ['ubicacion_id' => $ubic->id]);

            // 4.3) Rubros (principal + anexos) + rubro_id directo
            $ubic->rubros()->sync(array_values(array_unique(array_merge([$principal], $anexos))));
            $ubic->rubro_id = $principal ?: null;
            $ubic->save();

            // 4.4) Teléfonos
            foreach ($tels as $t) $ubic->telefonos()->create(['telefono'=>$t]);

            // 4.5) Disposiciones / Habilitaciones
            foreach (($this->state['disposiciones'] ?? []) as $d) {
                $num = trim((string)($d['numero'] ?? '')); if ($num==='') continue;
                $ubic->disposiciones()->create(['numero'=>$num,'fecha'=>!empty($d['fecha'])?$d['fecha']:null]);
            }
            foreach (($this->state['habilitaciones'] ?? []) as $h) {
                $num = trim((string)($h['numero'] ?? '')); if ($num==='') continue;
                $ubic->habilitaciones()->create(['numero'=>$num,'fecha'=>!empty($h['fecha'])?$h['fecha']:null]);
            }

            // 4.6) Historial estado
            if (method_exists($this, 'registrarHistorialEstado')) {
                $this->registrarHistorialEstado(
                    $ubic->id,
                    $estadoBase,
                    $estadoLabel,
                    $this->state['fecha_alta'] ?? null,
                    $this->state['fecha_baja'] ?? null,
                    $this->state['fecha_vto']  ?? null
                );
            }

            // 4.7) (Opcional) Movimiento legible
            try {
                $ubic->movimientos()->create([
                    'etapa'   => 'estado',
                    'detalle' => $estadoLabel,
                ]);
            } catch (\Throwable $e) { /* noop */ }
        });

        // --- 5) UI
        $this->dispatch('ubicacion-actualizada');
        $this->reset('state');
        $this->dispatch('hide-form', ['message' => 'Comercio creado correctamente.']);
    }


    public function updatedStateEstado($nuevo): void
    {
        // normalizá a base para decidir docs
        $base = $this->estadoBaseNormalize($nuevo); // 021/032/040/baja/etc.
        $esJuridica = ($this->state['persona_tipo'] ?? 'fisica') === 'juridica';
        $permitidos = $this->docKeysForEstado($base, $esJuridica);

        $docs = $this->state['documentos'] ?? [];
        // apago todos
        foreach (array_keys($this->docLabels) as $k) $docs[$k] = false;
        // enciendo solo los del estado
        foreach ($permitidos as $k) $docs[$k] = (bool)($docs[$k] ?? false);

        // reset del select de uso de inmueble
        $docs['doc_uso_inmueble_tipo'] = null;

        $this->state['documentos'] = $docs;
    }


    private function calcularSituacion(?string $estadoCanon, bool $esClausurado): ?string
    {
        if ($esClausurado) return 'clausurado';
        $e = trim(mb_strtolower((string)$estadoCanon));

        return match ($e) {
            // Alta para entramite/irregular y también 040
            'entramite','irregular','040' => 'alta',   // <-- NUEVO incluye 040
            'baja','baja_oficio','sin_efecto' => 'baja',
            default => null,
        };
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
