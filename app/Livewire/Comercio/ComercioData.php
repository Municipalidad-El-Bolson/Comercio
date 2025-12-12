<?php

namespace App\Livewire\Comercio;

use Livewire\Component;
use App\Models\Ubicacion;
use Illuminate\Support\Str;
use App\Models\Rubro;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Support\HandlesEstados;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use App\Models\UbicacionEstadoHist;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;          
use Carbon\Carbon;                      

class ComercioData extends Component
{
    use HandlesEstados;

    public Ubicacion $ubicacion;
    public bool $showEditModal = false;
    public string $rubroQuery = '';
    public string $anexoQuery = '';
    public array $rubroOpts = [];
    public array $anexoOpts = [];
    public $rubros = [];
    public array $state = [];
    public string $formKey = '';
    public bool $suspendEstadoHook = false; // <-- lo usás en editaComercio

    /** ====== Documentación (legacy para compat) ====== */
    public array $labelsGenerales = [
        'doc_libre_deuda_municipal'       => 'Certificado de libre deuda municipal',
        'doc_planeamiento_urbano'         => 'Dirección de Planeamiento Urbano',
        'doc_solicitud_habilitacion_pago' => 'Solicitud de habilitación + pago',
        'doc_comprobante_uso_local'       => 'Comprobante de uso del local',
        'doc_afip_constancia'             => 'Constancia de inscripción AFIP',
        'doc_recaudacion_rn'              => 'Constancia de inscripción Agencia Recaudación RN',
        'doc_fotocopia_dni'               => 'Fotocopia de DNI',
        'doc_comprobante_uso_inmueble'    => 'Comprobante de uso del inmueble',
        'doc_libre_deuda_tasas_inmueble'  => 'Libre deuda de tasas del inmueble',
        'doc_aptitud_tecnica_local'       => 'Certificado de aptitud técnica',
        'doc_cocap_rhi'                   => 'Certificado CO.CA.P.RHI',
        'doc_nota_carteleria_obras'       => 'Nota a Obras por cartelería',
        'doc_libro_actas_100'             => 'Libro de actas (100 hojas)',
    ];

    public array $labelsJuridicas = [
        'doc_acta_constitucion'   => 'Acta de constitución',
        'doc_contrato_societario' => 'Contrato societario',
        'doc_docs_representantes' => 'Documentación de representantes',
    ];

    public array $docDefaults = [
        'doc_libre_deuda_municipal'       => false,
        'doc_planeamiento_urbano'         => false,
        'doc_solicitud_habilitacion_pago' => false,
        'doc_comprobante_uso_local'       => false,
        'doc_afip_constancia'             => false,
        'doc_recaudacion_rn'              => false,
        'doc_fotocopia_dni'               => false,
        'doc_comprobante_uso_inmueble'    => false,
        'doc_libre_deuda_tasas_inmueble'  => false,
        'doc_aptitud_tecnica_local'       => false,
        'doc_cocap_rhi'                   => false,
        'doc_nota_carteleria_obras'       => false,
        'doc_libro_actas_100'             => false,
        // Jurídicas:
        'doc_acta_constitucion'           => false,
        'doc_contrato_societario'         => false,
        'doc_docs_representantes'         => false,
    ];

    /** Catálogo central de labels */
    private array $docLabels = [
        // General
        'doc_libre_deuda_municipal' => 'Certificado de libre deuda municipal',
        'doc_planeamiento_urbano'   => 'Dirección de Planeamiento Urbano',
        'doc_solicitud_habilitacion_pago' => 'Solicitud de habilitación + pago',
        'doc_comprobante_uso_local' => 'Comprobante de uso del local',
        'doc_afip_constancia'       => 'Constancia de inscripción emitida por AFIP',
        'doc_recaudacion_rn'        => 'Constancia de inscripción de Agencia de Recaudación Río Negro',
        'doc_fotocopia_dni'         => 'Fotocopia del DNI',
        'doc_comprobante_uso_inmueble'    => 'Comprobante de uso del inmueble',
        'doc_libre_deuda_tasas_inmueble'  => 'Libre deuda de tasas municipales',
        'doc_aptitud_tecnica_local' => 'Certificado de aptitud técnica del local',
        'doc_cocap_rhi'             => 'Certificado de CO.CA.P.R.HI',
        'doc_nota_carteleria_obras' => 'Nota a Obras Públicas declarando cartelería',
        'doc_libro_actas_100'       => 'Libro de actas de 100 hojas',
        // Jurídica
        'doc_acta_constitucion'     => 'Acta de constitución',
        'doc_contrato_societario'   => 'Contrato societario',
        'doc_docs_representantes'   => 'Documentación de representantes',
        // En trámite extra
        'doc_manipulacion_alimentos'=> 'Certificación de manipulación de alimentos',
        // Baja
        'doc_nota_baja'             => 'Nota de baja',
        'doc_pago_baja'             => 'Pago de baja',
        'doc_acta_inspeccion'       => 'Acta de inspección',
        // Irregular
        'doc_cert_electricidad'     => 'Certificado de electricidad',
        'doc_cert_gasista'          => 'Certificado de gasista',
        'doc_inf_seg_hig'           => 'Informe de seguridad e higiene',
        'doc_protocolo_mput'        => 'Protocolo de medición puesta a tierra',
        'doc_carga_fuego'           => 'Carga de fuego',
        'doc_inf_ascensores'        => 'Informe de ascensores',
        'doc_poliza_seguro'         => 'Póliza de seguro',
        'doc_cert_cocapri'          => 'Certificado de CO.CA.P.R.I',
        'doc_inf_splif'             => 'Informe del SPLIF',
        'doc_control_plagas'        => 'Control de plagas',
        'doc_cert_caldera'          => 'Certificado de caldera',
        'doc_cert_zavecom'          => 'Certificado de ZAVECOM',
        'doc_cert_salud_prov'       => 'Certificado de salud (Provincia)',
    ];

    /** Qué docs muestra cada estado */
    private function docKeysForEstado(string $estado, bool $esJuridica): array
    {
        $baseGeneral = [
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
        $juridica = $esJuridica
            ? ['doc_acta_constitucion','doc_contrato_societario','doc_docs_representantes']
            : [];

        return match ($estado) {
            'entramite', '021' => array_values(array_unique(array_merge(
                array_diff($baseGeneral, ['doc_nota_carteleria_obras','doc_planeamiento_urbano','doc_comprobante_uso_local']),
                ['doc_manipulacion_alimentos'],
                $juridica
            ))),
            'vigente'   => [],
            'baja','baja_oficio','exp_sin_efecto' => ['doc_nota_baja','doc_pago_baja','doc_libre_deuda_municipal','doc_acta_inspeccion'],
            'irregular','032' => [
                'doc_cert_electricidad','doc_cert_gasista','doc_inf_seg_hig','doc_protocolo_mput','doc_carga_fuego',
                'doc_inf_ascensores','doc_poliza_seguro','doc_cert_cocapri','doc_inf_splif','doc_control_plagas',
                'doc_cert_caldera','doc_cert_zavecom','doc_cert_salud_prov',
                'doc_comprobante_uso_inmueble',
            ],
            '040' => [],
            default => $baseGeneral
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
        $estado = $this->normalizarEstado($this->state['estado'] ?? 'entramite');
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

    /** ===== Helpers ===== */
    private function normalizarEstado(?string $estado): string
    {
        return $this->estadoBaseNormalize($estado);
    }

    private function chipsFor(\App\Models\Ubicacion $u): array
    {
        $base  = $u->estado_base ?: $this->estadoBaseNormalize($u->estado_label ?? $u->estado);
        $label = trim((string)($u->estado_label ?? ''));

        if ($label === '') {
            $label = match ($base) {
                '021' => '021',
                '032' => '032',
                '040' => '040',
                'baja' => 'Baja',
                'baja_oficio' => 'Baja de Oficio',
                'exp_sin_efecto','sin_efecto' => 'Expediente sin Efecto',
                default => strtoupper((string)$base),
            };
        }

        $cambio = null;
        if (preg_match('/^\s*(021|032)\s*-\s*(.+)$/ui', $label, $m)) {
            $base   = $m[1] === '021' ? '021' : '032';
            $cambio = trim($m[2]);
        }

        $estadoClass = match ($base) {
            '021' => 'badge-primary',
            '032' => 'badge-warning',
            '040' => 'badge-info',
            'baja','baja_oficio' => 'badge-danger',
            'exp_sin_efecto','sin_efecto' => 'badge-dark',
            default => 'badge-secondary',
        };

        $estadoLabel = match ($base) {
            '021' => '021',
            '032' => '032',
            '040' => '040',
            'baja' => 'Baja',
            'baja_oficio' => 'Baja de Oficio',
            'exp_sin_efecto','sin_efecto' => 'Expediente sin Efecto',
            default => strtoupper((string)$base),
        };

        $cambioLabel = $cambio ?: 'Ninguno';
        $cambioClass = $cambio ? 'badge-info' : 'badge-light';

        return [
            'estadoChip' => ['label' => $estadoLabel, 'class' => $estadoClass],
            'cambioChip' => ['label' => $cambioLabel, 'class' => $cambioClass],
        ];
    }

    private function estadoBaseNormalizeFromRaw(?string $raw): string
    {
        $s = trim(mb_strtolower((string)$raw));
        if ($s === '') return '021';

        if (str_starts_with($s, '021')) return '021';
        if (str_starts_with($s, '032')) return '032';
        if (str_starts_with($s, '040')) return '040';

        return match ($s) {
            'entramite','en tramite','en trámite','en_tramite','en-tramite','vigente','alta' => '021',
            'irregular' => '032',
            '040','040/25' => '040',
            'baja' => 'baja',
            'baja de oficio','baja_oficio','baja-oficio' => 'baja_oficio',
            'expediente sin efecto','sin_efecto','exp_sin_efecto','exp-sin-efecto' => 'exp_sin_efecto',
            default => '021',
        };
    }

    private function inferCambioKeyFromEstado(string $estadoRaw, string $base): ?string
    {
        if (!str_contains($estadoRaw, '-')) return '';
        [, $label] = array_map('trim', explode('-', $estadoRaw, 2));
        if ($label === '') return '';

        $opts = $this->cambiosOptionsByBase($base);
        foreach ($opts as $key => $lbl) {
            if (mb_strtolower($lbl) === mb_strtolower($label)) {
                return $key;
            }
        }
        return '';
    }

    private function calcularSituacion(?string $estado, bool $esClausurado): ?string
    {
        if ($esClausurado) return 'clausurado';
        $estado = $this->normalizarEstado($estado ?? '');
        return match ($estado) {
            'vigente' => 'alta',
            'baja' => 'baja',
            default => null,
        };
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
        $out['doc_afip_constancia'] =
            (bool)($out['doc_afip_constancia'] ?? false)
         || (bool)($out['doc_afip_constancia_fisica'] ?? false)
         || (bool)($out['doc_afip_constancia_juridica'] ?? false);

        return $out;
    }

    private function normalizeDecimal($v): ?string
    {
        if ($v === null || $v === '') return null;
        $s = str_replace(' ', '', trim((string)$v));
        $hasDot = str_contains($s,'.'); $hasComma = str_contains($s,',');
        if ($hasDot && $hasComma) {
            $lastDot = strrpos($s,'.'); $lastComma = strrpos($s,',');
            if ($lastComma > $lastDot) { $s = str_replace('.','',$s); $s = str_replace(',', '.', $s); }
            else { $s = str_replace(',', '', $s); }
        } elseif ($hasComma) { $s = str_replace(',', '.', $s); }
        if (!is_numeric($s)) return null;
        return number_format((float)$s, 2, '.', '');
    }

    /*** >>>>>>>>>>>>>>>>>>  MÉTODO QUE FALTABA  <<<<<<<<<<<<<<<<<< */
    protected function reglasComunes(bool $isUpdate = false): array
    {
        return [
            // acepta canónicos y también bases (incluye 040)
            'state.estado'    => ['required', Rule::in([
                'entramite','irregular','baja','baja_oficio','sin_efecto','040',
                '021','032','040','exp_sin_efecto',
            ])],
            'state.tipo_hab'  => ['nullable', Rule::in(['definitiva','prev'])],

            'state.fecha_alta' => ['nullable','date'],
            'state.fecha_baja' => ['nullable','date'],
            'state.fecha_vto'  => ['nullable','date'],

            'state.observaciones' => ['nullable','string','max:500'],
        ];
    }

    /*** y este helper que usás en editaComercio ***/
    private function parseCambioDesdeEstado(string $estadoRaw): array
    {
        $raw = trim($estadoRaw);
        $sl  = mb_strtolower($raw);

        if (str_starts_with($sl, '021'))       { $base = '021'; }
        elseif (str_starts_with($sl, '032'))   { $base = '032'; }
        elseif (str_starts_with($sl, '040'))   { $base = '040'; }
        elseif (in_array($sl, ['entramite','en tramite','en trámite','en_tramite','en-tramite','alta','vigente'])) { $base = '021'; }
        elseif ($sl === 'irregular')           { $base = '032'; }
        elseif (in_array($sl, ['baja','baja de oficio','baja_oficio','baja-oficio','expediente sin efecto','sin_efecto','exp_sin_efecto'])) {
            return ['base' => $sl, 'cambio_key' => null];
        } else { $base = '021'; }

        $label = '';
        if (str_contains($raw, '-')) {
            $label = trim(explode('-', $raw, 2)[1] ?? '');
        }
        if ($label === '') return ['base' => $base, 'cambio_key' => null];

        $opts = $this->cambiosOptionsByBase($base);
        $buscado = mb_strtolower($label);
        foreach ($opts as $key => $txt) {
            if (mb_strtolower($txt) === $buscado) {
                return ['base' => $base, 'cambio_key' => $key];
            }
        }
        return ['base' => $base, 'cambio_key' => null];
    }
    /*** -------------------------------------------------------- ***/

    public function editaComercio(Ubicacion $ubicacion)
    {
        $this->showEditModal = true;

        $this->ubicacion = $ubicacion->loadMissing([
            'rubro','rubros','telefonos','disposiciones','habilitaciones','documentos',
        ]);

        $this->state['alojamiento_unidades'] = $this->ubicacion->alojamiento_unidades ?? null;
        $this->state['alojamiento_plazas'] = $this->ubicacion->alojamiento_plazas ?? null;

        $this->state['camping_fogones'] = $this->ubicacion->camping_fogones ?? null;
        $this->state['camping_dormis'] = $this->ubicacion->camping_dormis ?? null;
        $this->state['camping_otros_servicios'] = $this->ubicacion->camping_otros_servicios ?? null;


        // State base desde el modelo
        $this->state = $this->ubicacion->toArray();
        $this->state['es_clausurado'] = ($this->ubicacion->situacion === 'clausurado');

        // Cadena "cruda" para parsear cambio (priorizo estado_label si existe)
        $estadoCrudo = (string) (
            $this->ubicacion->getOriginal('estado_label')
            ?? $this->ubicacion->estado_label
            ?? $this->ubicacion->getOriginal('estado')
            ?? $this->ubicacion->estado
            ?? ''
        );

        // Base desde crudo/legacy → '021' | '032' | '040' | 'baja' | 'baja_oficio' | 'exp_sin_efecto'
        $base = $this->estadoBaseNormalizeFromRaw($estadoCrudo);

        // Silencio hook mientras seteo
        $this->suspendEstadoHook = true;

        // Setear estado en base (021/032/040/...)
        $this->state['estado'] = $this->normalizarEstado($base);

        // Detectar “cambio”
        $parsed = $this->parseCambioDesdeEstado($estadoCrudo);
        $this->state['cambio_tipo'] = $parsed['cambio_key'] ?: $this->inferCambioKeyFromEstado($estadoCrudo, $base);

        $this->suspendEstadoHook = false;

        // Situación (si no estaba)
        $this->state['situacion'] = $this->ubicacion->situacion ?? ($this->state['situacion'] ?? null);

        // Fechas -> Y-m-d  (¡esto faltaba!)
        $toYmd = function ($v): ?string {
            if (empty($v)) return null;
            if ($v instanceof \DateTimeInterface) return $v->format('Y-m-d');
            try { return Carbon::parse($v)->format('Y-m-d'); } catch (\Throwable $e) { return null; }
        };
        $this->state['fecha_alta'] = $toYmd($this->ubicacion->fecha_alta);
        $this->state['fecha_baja'] = $toYmd($this->ubicacion->fecha_baja);
        $this->state['fecha_vto']  = $toYmd($this->ubicacion->fecha_vto);

        // Rubro principal / anexos
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

        // Teléfonos
        $tels = $this->ubicacion->telefonos->pluck('telefono')->filter()->values()->all();
        $this->state['telefonos'] = !empty($tels) ? $tels : [''];

        // Disposiciones / Habilitaciones
        $this->state['disposiciones'] = $this->ubicacion->disposiciones->map(fn($d)=>[
            'numero'=>(string)$d->numero, 'fecha'=>$toYmd($d->fecha),
        ])->values()->all();
        if (empty($this->state['disposiciones'])) $this->state['disposiciones'] = [['numero'=>'','fecha'=>null]];

        $this->state['habilitaciones'] = $this->ubicacion->habilitaciones->map(fn($h)=>[
            'numero'=>(string)$h->numero, 'fecha'=>$toYmd($h->fecha),
        ])->values()->all();
        if (empty($this->state['habilitaciones'])) $this->state['habilitaciones'] = [['numero'=>'','fecha'=>null]];

        // Documentos desde BD → normalizados → merge con defaults
        $docsDb = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $docsUi = $this->normalizeDocsArray($docsDb);
        $this->state['documentos'] = array_merge($this->docDefaults, $docsUi);

        // Helpers de N° únicos
        $this->state['numero_disposicion']  = (string) data_get($this->state, 'disposiciones.0.numero', '');
        $this->state['numero_habilitacion'] = (string) data_get($this->state, 'habilitaciones.0.numero', '');

        // Ready
        $this->formKey = (string) Str::uuid();
        $this->dispatch('show-form', rubroId: ($this->state['rubro_id'] ?? null), anexos: ($this->state['rubros_anexos'] ?? []));
        $this->dispatch('refresh-selects', rubroId: ($this->state['rubro_id'] ?? null), anexos: ($this->state['rubros_anexos'] ?? []));
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

    public function updateComercio()
    {
        // ---- 0) Mapear estado ----
        $rawEstado   = $this->state['estado'] ?? $this->ubicacion->estado ?? 'entramite';
        $estadoBase  = $this->estadoBaseNormalize($rawEstado);
        $estadoCanon = $this->mapBaseToCanon($estadoBase);
        $cambioKey   = trim((string)($this->state['cambio_tipo'] ?? ''));

        if (($cambioKey === '' || $cambioKey === null) && in_array($estadoBase, ['021','032'], true)) {
            $cambioKey = $this->inferCambioKeyFromEstado(
                $this->ubicacion->estado_label ?? $this->ubicacion->estado ?? '',
                $estadoBase
            );
        }
        $estadoLabel = $this->buildEstadoLabel($estadoBase, $cambioKey);

        // Para validar, forzamos el canónico
        $tmpState = $this->state;
        $tmpState['estado'] = $estadoCanon;

        // ---- 1) Reglas comunes ----
        $rules = $this->reglasComunes(true);
        \Validator::make(['state'=>$tmpState], $rules)->validate();

        // ---- 2) Normalizaciones ----
        $validated = $this->state;

        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $c) {
            if (!empty($validated[$c] ?? null)) $validated[$c] = \Illuminate\Support\Str::title($validated[$c]);
        }

        $validated['dni_cuit'] = preg_replace('/\D/','', $validated['dni_cuit'] ?? '');

        if (($validated['persona_tipo'] ?? 'fisica') === 'juridica') {
            $validated['apellido'] = null;
            $validated['nombres']  = null;
        }

        if (array_key_exists('monto_pagar', $validated)) {
            $validated['monto_pagar'] = $this->normalizeDecimal($validated['monto_pagar']);
        }

        // ---- 3) Estado, situación ----
        $validated['estado']       = $estadoCanon;
        $validated['estado_base']  = $estadoBase;
        $validated['estado_label'] = $estadoLabel;
        $validated['situacion']    = $this->calcularSituacion($estadoCanon, (bool)($this->state['es_clausurado'] ?? false));

        // ---- 3b) ALOJAMIENTO (CORREGIDO) ----
        $validated['alojamiento_unidades'] = $this->state['alojamiento_unidades'] ?? null;
        $validated['alojamiento_plazas']   = $this->state['alojamiento_plazas'] ?? null;

        $validated['camping_fogones']   = $this->state['camping_fogones'] ?? null;
        $validated['camping_dormis']   = $this->state['camping_dormis'] ?? null;
        $validated['camping_otros_servicios']   = $this->state['camping_otros_servicios'] ?? null;

        // ---- 4) Reglas específicas ----
        $rules = [
            'persona_tipo'          => 'required|in:fisica,juridica',
            'apellido'              => 'nullable|string|min:2|max:60',
            'nombres'               => 'nullable|string|min:2|max:80',
            'razon_social'          => 'nullable|string|min:2|max:120',
            'dni_cuit'              => 'nullable|string',
            'rubro_id'              => 'required|exists:rubros,id',
            'rubros_anexos'         => 'array',
            'rubros_anexos.*'       => 'integer|exists:rubros,id|different:rubro_id|distinct',
            'domicilio_responsable' => 'nullable|string|min:3|max:160',
            'correo'                => 'nullable|email:rfc,dns|max:120',
            'nombre_comercial'      => 'nullable|string|min:2|max:120',
            'domicilio_comercio'    => 'nullable|string|min:3|max:160',
            'nomenclatura'          => 'nullable|string|max:80',
            'observaciones'         => 'nullable|string|max:500',
            'estado'                => 'required|in:entramite,irregular,baja,baja_oficio,sin_efecto,040',
            'tipo_hab'              => 'required|in:definitiva,prev',
            'fecha_alta'            => 'nullable|date',
            'fecha_baja'            => 'nullable|date',
            'fecha_vto'             => 'nullable|date',
            'documentos'            => 'array',
            'es_clausurado'         => 'boolean',
            'telefonos'             => 'array|min:1',
            'telefonos.*'           => ['nullable','regex:/^[\d\s()+\-]{6,20}$/'],
            'disposiciones'           => 'array',
            'disposiciones.*.numero'  => 'nullable|string|max:60',
            'disposiciones.*.fecha'   => 'nullable|date',
            'habilitaciones'          => 'array',
            'habilitaciones.*.numero' => 'nullable|string|max:60',
            'habilitaciones.*.fecha'  => 'nullable|date',

            'alojamiento_unidades' => ['nullable', 'integer', 'min:0'],
            'alojamiento_plazas'   => ['nullable', 'integer', 'min:0'],

            'camping_fogones'      => ['nullable', 'integer', 'min:0'],
            'camping_dormis'       => ['nullable', 'integer', 'min:0'],
            'camping_otros_servicios' => ['nullable', 'string', 'max:255'],
        ];

        // Tipo de persona
        if (($this->state['persona_tipo'] ?? 'fisica') === 'fisica') {
            $rules['apellido'] = 'required|string|min:2|max:60';
            $rules['nombres']  = 'required|string|min:2|max:80';
        } else {
            $rules['razon_social'] = 'required|string|min:2|max:120';
        }

        $prevCanon      = $this->normalizarEstado($this->ubicacion->getOriginal('estado') ?? $this->ubicacion->estado ?? 'entramite');
        $yaTeniaAlta    = !empty($this->ubicacion?->fecha_alta);
        $vieneAltaAhora = !empty($this->state['fecha_alta']);

        switch ($estadoCanon) {
            case 'entramite':
                break;
            case 'irregular':
                $rules['fecha_alta'] = 'nullable|date';
                break;
            case 'baja':
            case 'baja_oficio':
            case 'sin_efecto':
                $tieneAltaAntes = $yaTeniaAlta || $vieneAltaAhora;
                $rules['fecha_baja'] = 'nullable|date' . ($tieneAltaAntes ? '|after_or_equal:fecha_alta' : '') . '|before_or_equal:today';
                if (!$tieneAltaAntes) {
                    $rules['fecha_alta'] = 'nullable|date|before_or_equal:today';
                }
                break;
        }

        // ---- 5) Validación final ----
        $validated = \Validator::make($validated, $rules)->validate();

         // Normalizar strings
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $c) {
            if (!empty($validated[$c] ?? null)) $validated[$c] = Str::title($validated[$c]);
        }
        $validated['dni_cuit'] = preg_replace('/\D/','', $validated['dni_cuit'] ?? '');

        // Situación final
        if (in_array('situacion', Schema::getColumnListing('ubicaciones'), true)) {
            $validated['situacion'] = $this->calcularSituacion($estadoCanon, (bool)($this->state['es_clausurado'] ?? false));
        }

        // Estados correctos
        $validated['estado']       = $estadoCanon;
        $validated['estado_base']  = $estadoBase;
        $validated['estado_label'] = $estadoLabel;

        unset($validated['domicilio_responsable'], $validated['es_clausurado']);

        // Re-geocodificar si cambió la dirección

        // ---- 6) Geocoding si cambió la dirección ----
        $dirVieja = trim((string)$this->ubicacion->getOriginal('domicilio_comercio'));
        $dirNueva = trim((string)($validated['domicilio_comercio'] ?? ''));

        if ($dirNueva !== '' && $dirNueva !== $dirVieja) {
            $enricher = app(\App\Services\UbicacionGeoEnricher::class);
            $validated = $enricher->enrich($validated);
        }

        // ---- 7) Filtrar solo columnas reales ----
        $colsUbic = Schema::getColumnListing('ubicaciones');
        $dataUbic = array_intersect_key($validated, array_flip($colsUbic));

        // ---- 8) Documentos ----
        $docsFromUI = $this->state['documentos'] ?? [];

        DB::transaction(function () use ($dataUbic, $docsFromUI, $estadoBase, $estadoLabel) {

            if (array_key_exists('estado', $dataUbic)) {
                $dataUbic['estado'] = $this->mapBaseToCanon(
                    $this->estadoBaseNormalize((string)$dataUbic['estado'])
                );
            }

            $this->ubicacion->update($dataUbic);
            $this->ubicacion->refresh();

            // Clausura
            $this->state['es_clausurado'] = $this->ubicacion->situacion === 'clausurado';

            // Documentos
            $incoming = $this->normalizeDocsArray($docsFromUI);
            $tipo = $incoming['doc_uso_inmueble_tipo'] ?? null;

            foreach (['doc_uso_boleto','doc_uso_contrato','doc_uso_comodato','doc_uso_titulo','doc_uso_cert_ocupacion'] as $k) {
                $incoming[$k] = false;
            }

            $map = [
                'boleto'         => 'doc_uso_boleto',
                'contrato'       => 'doc_uso_contrato',
                'comodato'       => 'doc_uso_comodato',
                'titulo'         => 'doc_uso_titulo',
                'cert_ocupacion' => 'doc_uso_cert_ocupacion',
            ];
            if ($tipo && isset($map[$tipo])) $incoming[$map[$tipo]] = true;

            $colsDocs = Schema::getColumnListing('ubicacion_documentos');
            $actualBD = $this->ubicacion->documentos?->toArray() ?? [];
            $actual   = array_intersect_key($actualBD, array_flip($colsDocs));
            $nuevo    = array_intersect_key($incoming, array_flip($colsDocs));

            unset($nuevo['id'], $nuevo['created_at'], $nuevo['updated_at'], $nuevo['ubicacion_id']);

            $payload = array_merge($actual, $nuevo);
            $this->ubicacion->documentos()->updateOrCreate(['ubicacion_id' => $this->ubicacion->id], $payload);

            // Rubros
            $principal = (int)($this->state['rubro_id'] ?? 0);
            $anexos = collect($this->state['rubros_anexos'] ?? [])->map(fn($v)=>(int)$v)->filter()
                        ->reject(fn($id)=>$id === $principal)->unique()->values()->all();
            $this->ubicacion->rubros()->sync(array_values(array_unique(array_merge([$principal], $anexos))));
            $this->ubicacion->rubro_id = $principal ?: null;
            $this->ubicacion->save();

            // Teléfonos
            $this->ubicacion->telefonos()->delete();
            $telSan = collect($this->state['telefonos'] ?? [])->map(fn($t)=>trim((string)$t))->filter()->unique()->values();
            foreach ($telSan as $t) $this->ubicacion->telefonos()->create(['telefono'=>$t]);

            // Disposiciones
            $this->ubicacion->disposiciones()->delete();
            foreach (($this->state['disposiciones'] ?? []) as $d) {
                $num = trim((string)($d['numero'] ?? '')); if ($num==='') continue;
                $this->ubicacion->disposiciones()->create(['numero'=>$num,'fecha'=>!empty($d['fecha'])?$d['fecha']:null]);
            }

            // Habilitaciones
            $this->ubicacion->habilitaciones()->delete();
            foreach (($this->state['habilitaciones'] ?? []) as $h) {
                $num = trim((string)($h['numero'] ?? '')); if ($num==='') continue;
                $this->ubicacion->habilitaciones()->create(['numero'=>$num,'fecha'=>!empty($h['fecha'])?$h['fecha']:null]);
            }

            // Historial
            $this->registrarHistorialEstado(
                $this->ubicacion,
                $estadoBase,
                $estadoLabel,
                $this->state['fecha_alta'] ?? null,
                $this->state['fecha_baja'] ?? null,
                $this->state['fecha_vto']  ?? null
            );
        });

        // ---- 9) UI ----
        $this->dispatch('ubicacion-actualizada', id: $this->ubicacion->id);
        $this->dispatch('hide-form', ['message' => 'Registro actualizado correctamente']);
        $this->ubicacion->refresh()->load('documentos','rubros','telefonos','disposiciones','habilitaciones');
        $this->state['persona_tipo'] = $this->ubicacion->persona_tipo ?? 'fisica';
        $this->state['estado']       = $this->normalizarEstado($this->ubicacion->estado ?? 'entramite');

    }

    public function eliminarMovimiento($id)
    {
        $mov = \App\Models\Movimiento::find($id);

        if (!$mov) {
            return;
        }

        // Borrar archivo si existe
        if ($mov->archivo && \Storage::disk('public')->exists($mov->archivo)) {
            \Storage::disk('public')->delete($mov->archivo);
        }

        $mov->delete();

        $this->dispatch('toast', mensaje: 'Movimiento eliminado correctamente', tipo: 'success');
    }


    /** ====== Repeater Teléfonos ====== */
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

    public function updatedStateEstado($nuevo): void
    {
        if ($this->suspendEstadoHook) return;

        $estado = $this->normalizarEstado($nuevo);
        $esJuridica = ($this->state['persona_tipo'] ?? 'fisica') === 'juridica';
        $permitidos = $this->docKeysForEstado($estado, $esJuridica);

        $docs = $this->state['documentos'] ?? [];
        foreach (array_keys($this->docLabels) as $k) $docs[$k] = false;
        foreach ($permitidos as $k) $docs[$k] = (bool)($docs[$k] ?? false);
        $docs['doc_uso_inmueble_tipo'] = null;
        $this->state['documentos'] = $docs;
    }

    public function deleteComercio(): void
    {
        abort_unless(Gate::allows('manage-ubicaciones'), 403);

        DB::transaction(function () {
            $u = $this->ubicacion->loadMissing([
                'rubros', 'telefonos', 'disposiciones', 'habilitaciones', 'documentos', 'movimientos'
            ]);

            $u->rubros()->detach();
            $u->telefonos()->delete();
            $u->disposiciones()->delete();
            $u->habilitaciones()->delete();
            if ($u->documentos) { $u->documentos()->delete(); }
            $u->movimientos()->delete();

            $u->delete();
        });

        $this->redirect('/ubicaciones', navigate: true);
    }

    /** ====== Ciclo de vida ====== */
    public function mount(Ubicacion $ubicacion)
    {
        $this->ubicacion = $ubicacion->load('rubro', 'rubros', 'documentos', 'movimientos', 'telefonos', 'disposiciones', 'habilitaciones');

        $this->rubroOpts = Rubro::orderBy('subrubro')->get(['id','subrubro'])->toArray();
        $this->anexoOpts = $this->rubroOpts;

        $this->state = [
            'persona_tipo'  => $this->ubicacion->persona_tipo ?? 'fisica',
            'estado'        => $this->normalizarEstado($this->ubicacion->estado ?? 'entramite'),
            'rubro_id'      => $this->ubicacion->rubro_id,
            'rubros_anexos' => [],
        ];

        $this->formKey = (string) Str::uuid();
    }

    #[On('ubicacion-actualizada')]
    public function refrescarDatos($id = null): void
    {
        if (!$id || (int)$id === (int)$this->ubicacion->id) {
            $this->ubicacion->refresh()->load(
                'rubro','rubros','telefonos','documentos','movimientos','disposiciones','habilitaciones'
            );
            $this->state['persona_tipo'] = $this->ubicacion->persona_tipo ?? 'fisica';
            $this->state['estado']       = $this->normalizarEstado($this->ubicacion->estado ?? 'entramite');
        }
    }

    /** ====== Búsqueda de rubros ====== */
    public function updatedRubroQuery(string $q): void
    {
        $q = trim($q);
        $this->rubroOpts = Rubro::when($q !== '', fn($qq)=>$qq->where('subrubro','like',"%{$q}%"))
            ->orderBy('subrubro')->get(['id','subrubro'])->toArray();
    }

    public function updatedAnexoQuery(string $q): void
    {
        $q = trim($q);
        $this->anexoOpts = Rubro::when($q !== '', fn($qq)=>$qq->where('subrubro','like',"%{$q}%"))
            ->orderBy('subrubro')->get(['id','subrubro'])->toArray();
    }

    private function mergeOpts(array $opts, array $extra): array
    {
        $byId = [];
        foreach ($opts as $op) $byId[$op['id']] = $op;
        foreach ($extra as $op) $byId[$op['id']] = $op;
        return array_values($byId);
    }

    public function updatedStatePersonaTipo($tipo): void
    {
        if ($tipo === 'fisica') {
            $this->state['documentos'] = $this->state['documentos'] ?? [];
            foreach (['doc_acta_constitucion','doc_contrato_societario','doc_docs_representantes'] as $k) {
                $this->state['documentos'][$k] = false;
            }
        }
    }

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
            if (count($args) >= 5 && is_numeric($args[0]) && is_numeric($args[1])) {
                [$lat,$lng,$direccion,$barrio,$nomen] = [$args[0],$args[1],$args[2],$args[3],$args[4]];
            }
            elseif (count($args) >= 3) {
                [$a,$b,$c] = [$args[0],$args[1],$args[2]];
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

        if ($nomen !== null && $isCoord($nomen)) { $nomen = null; }

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
            'domicilio_comercio' => $direccion,
            'barrio'             => $barrio,
            'nomenclatura'       => $nomen ?? '',
            'correo'             => '',
            'telefono'           => '',
            'monto_pagar'        => null,
            'observaciones'      => '',
            'telefonos'          => [''],
            'rubros_anexos'      => [],
            'alojamiento_unidades' => null,
            'alojamiento_plazas'   => null,
            'camping_fogones'       => null,
            'camping_dormis'        => null,
            'camping_otros_servicios'=> null,
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

    private function registrarHistorialEstado(
        Ubicacion|int|null $ubic,
        string $estadoBase,
        string $estadoLabel,
        $fechaAlta = null,
        $fechaBaja = null,
        $fechaVto  = null
    ): void {
        $ubicacionId = $ubic instanceof \App\Models\Ubicacion
            ? $ubic->id
            : (is_numeric($ubic) ? (int)$ubic : null);

        if (!$ubicacionId) return;

        // Normalizar base y fechas (strings vacíos → null)
        $base = $this->estadoBaseNormalize($estadoBase);
        $norm = function($v) {
            if ($v instanceof \DateTimeInterface) return $v->format('Y-m-d');
            $s = is_string($v) ? trim($v) : $v;
            if ($s === '' || $s === null) return null;
            try { return \Carbon\Carbon::parse($s)->format('Y-m-d'); } catch (\Throwable $e) { return null; }
        };

        $fAlta = $norm($fechaAlta);
        $fBaja = $norm($fechaBaja);
        $fVto  = $norm($fechaVto);

        // Regla de negocio: limpiar fechas que NO aplican al estado
        switch ($base) {
            case '021':
            case '032':
            case '040':
                // Estados de "alta/trámite": no debe haber fecha_baja
                $fBaja = null;
                break;

            case 'baja':
            case 'baja_oficio':
            case 'exp_sin_efecto':
                // Estados de baja: no debe haber fecha_vto
                $fVto = null;
                break;
        }

        // Anti-duplicado: si el último registro tiene exactamente lo mismo, no grabes
        $last = \App\Models\UbicacionEstadoHist::where('ubicacion_id', $ubicacionId)
                    ->latest('id')->first();

        if ($last
            && $last->estado_base  === $base
            && $last->estado_label === $estadoLabel
            && $last->fecha_alta?->format('Y-m-d') === $fAlta
            && $last->fecha_baja?->format('Y-m-d') === $fBaja
            && $last->fecha_vto?->format('Y-m-d')  === $fVto) {
            return; // nada cambió realmente
        }

        \App\Models\UbicacionEstadoHist::create([
            'ubicacion_id' => $ubicacionId,
            'estado_base'  => $base,
            'estado_label' => $estadoLabel,
            'fecha_alta'   => $fAlta,
            'fecha_baja'   => $fBaja,
            'fecha_vto'    => $fVto,
            'user_id'      => auth()->id(),
        ]);
    }


    #[On('prefill-desde-mapa')]
    public function prefillDesdeMapa($direccion = null, $barrio = null, $nomenclatura = null)
    {
        $this->state['domicilio_comercio'] = $direccion ?? '';
        $this->state['barrio']             = $barrio ?? '';
        $this->state['nomenclatura']       = $nomenclatura ?? '';
        $this->dispatch('show-form', rubroId: ($this->state['rubro_id'] ?? null), anexos: ($this->state['rubros_anexos'] ?? []));
    }

    public function getEsAlojamientoProperty(): bool
    {
        $id = $this->state['rubro_id'] ?? null;
        if (!$id) return false;

        $rubro = \App\Models\Rubro::find($id);
        if (!$rubro) return false;

        return strtoupper(trim($rubro->subrubro)) === 'ALOJAMIENTO';
    }

    public function render()
    {
        $chips = $this->chipsFor($this->ubicacion);
        $this->ubicacion->loadMissing('rubros','telefonos');
        $historial = $this->ubicacion->movimientos()
            ->where('etapa','estado')
            ->latest('id')
            ->get();

        $docsDB = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $docs   = $this->normalizeDocsArray($docsDB);

        $schema = $this->docSchema;

        $total = count($schema['items']);
        $presentadas = 0;
        foreach ($schema['items'] as $it) {
            if (!empty($docs[$it['key']])) $presentadas++;
        }

        $esJuridica = ($this->state['persona_tipo'] ?? 'fisica') === 'juridica';

        return view('livewire.comercio.comercio-data', [
            'ubicacion'  => $this->ubicacion,
            'historial'  => $historial,
            'rubros'     => $this->rubros,
            'docs'       => $docs,
            'schema'     => $schema,
            'esJuridica' => $esJuridica,
            'docsTotal'  => $total,
            'docsOK'     => $presentadas,
            'estadoChip' => $chips['estadoChip'],
            'cambioChip' => $chips['cambioChip'],
        ])->layout('admin.layouts.app');
    }
}

