<?php

namespace App\Livewire\Comercio;

use Livewire\Component;
use App\Models\Ubicacion;
use Illuminate\Support\Str;
use App\Models\Rubro;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class ComercioData extends Component
{
    public Ubicacion $ubicacion;

    public bool $showEditModal = false;

    public string $rubroQuery = '';
    public string $anexoQuery = '';
    public array $rubroOpts = [];
    public array $anexoOpts = [];

    public $rubros = [];
    public array $state = [];
    public string $formKey = '';

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
            // entrámite: base – cartelería – PU – “uso del local” + manipulación de alimentos
            'entramite' => array_values(array_unique(array_merge(
                array_diff($baseGeneral, ['doc_nota_carteleria_obras','doc_planeamiento_urbano','doc_comprobante_uso_local']),
                ['doc_manipulacion_alimentos'],
                $juridica
            ))),
            // vigente: no pedimos nada
            'vigente'   => [],
            // baja: sólo estos
            'baja'      => ['doc_nota_baja','doc_pago_baja','doc_libre_deuda_municipal'],
            // irregular: lista específica + “uso de inmueble”
            'irregular' => [
                'doc_cert_electricidad','doc_cert_gasista','doc_inf_seg_hig','doc_protocolo_mput','doc_carga_fuego',
                'doc_inf_ascensores','doc_poliza_seguro','doc_cert_cocapri','doc_inf_splif','doc_control_plagas',
                'doc_cert_caldera','doc_cert_zavecom','doc_cert_salud_prov',
                'doc_comprobante_uso_inmueble',
            ],
            // baja de oficio / sin efecto: por ahora sin lista especial
            'baja_oficio','sin_efecto' => [],
            default     => $baseGeneral
        };
    }

    /** Opciones del select de “Uso de inmueble” */
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

    /** Schema listo para Blade */
    public function getDocSchemaProperty(): array
    {
        $estado = $this->normalizarEstado($this->state['estado'] ?? 'entramite');
        $esJuridica = ($this->state['persona_tipo'] ?? 'fisica') === 'juridica';

        $keys = $this->docKeysForEstado($estado, $esJuridica);

        $items = [];
        foreach ($keys as $k) {
            $items[] = [
                'key'   => $k,
                'label' => $this->docLabels[$k] ?? $k,
                'type'  => 'checkbox',
            ];
        }

        // Uso de inmueble: checkbox + select
        $showUsoInmueble = in_array('doc_comprobante_uso_inmueble', $keys, true) || $estado === 'entramite';
        $uso = [
            'show'        => $showUsoInmueble,
            'checkboxKey' => 'doc_comprobante_uso_inmueble',
            'selectKey'   => 'doc_uso_inmueble_tipo',
            'label'       => 'Uso de inmueble',
            'options'     => $this->usoInmuebleOptions(),
        ];

        return ['items' => $items, 'uso_inmueble' => $uso];
    }

    /** ===== Helpers ===== */
    private function normalizarEstado(?string $estado): string
    {
        $e = trim(mb_strtolower($estado ?? ''));
        return match ($e) {
            'en tramite','en trámite','en_tramite','en-tramite','021' => 'entramite',
            'vigente','alta' => 'vigente',
            'irregular','032' => 'irregular',
            'baja' => 'baja',
            'baja de oficio','baja_oficio' => 'baja_oficio',
            'expediente sin efecto','sin_efecto' => 'sin_efecto',
            default => 'entramite',
        };
    }

    private function calcularSituacion(?string $estado, bool $esClausurado): ?string
    {
        if ($esClausurado) return 'clausurado';
        $estado = $this->normalizarEstado($estado ?? '');
        return match ($estado) {
            'vigente'   => 'alta',
            'baja'      => 'baja',
            default     => null, // entramite / irregular / baja_oficio / sin_efecto
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

        // Unificación AFIP (legacy → actual)
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

    /** ========= EDITAR Y GUARDAR ========= */
    public function editaComercio(Ubicacion $ubicacion)
    {
        $this->showEditModal = true;

        $this->ubicacion = $ubicacion->loadMissing([
            'rubro','rubros','documentos','telefonos','disposiciones','habilitaciones','movimientos',
        ]);

        // base del state
        $this->state = $this->ubicacion->toArray();
        $this->state['es_clausurado'] = ($this->ubicacion->situacion === 'clausurado');

        // fechas para inputs
        foreach (['fecha_alta','fecha_baja','fecha_vto'] as $f) {
            $this->state[$f] = !empty($this->ubicacion->{$f}) ? $this->ubicacion->{$f}->format('Y-m-d') : null;
        }
        $this->state['estado'] = $this->normalizarEstado($this->state['estado'] ?? $this->ubicacion->estado ?? 'entramite');

        // rubros (principal + anexos)
        $idsOrdenados = $this->ubicacion->rubros
            ->map(fn($r) => ['id' => $r->id, 'orden' => $r->pivot->orden ?? 9999])
            ->sortBy('orden')->pluck('id')->values()->all();
        $principal = $this->ubicacion->rubro_id ?: ($idsOrdenados[0] ?? null);
        $anexos = collect($idsOrdenados)->filter(fn($id) => $id !== $principal)->values()->all();
        $this->state['rubro_id'] = $principal;
        $this->state['rubros_anexos'] = $anexos;

        // teléfonos
        $tels = $this->ubicacion->telefonos->pluck('telefono')->filter()->values()->all();
        $this->state['telefonos'] = !empty($tels) ? $tels : [''];

        // repetidores
        $this->state['disposiciones'] = $this->ubicacion->disposiciones->map(fn($d)=>[
            'numero'=>(string)$d->numero,
            'fecha' =>$d->fecha?->format('Y-m-d'),
        ])->values()->all() ?: [['numero'=>'','fecha'=>null]];

        $this->state['habilitaciones'] = $this->ubicacion->habilitaciones->map(fn($h)=>[
            'numero'=>(string)$h->numero,
            'fecha' =>$h->fecha?->format('Y-m-d'),
        ])->values()->all() ?: [['numero'=>'','fecha'=>null]];

        // documentos desde BD → state
        $docsRaw = $this->ubicacion->documentos?->toArray() ?? [];
        $this->state['documentos'] = $this->normalizeDocsArray($docsRaw);

        $this->formKey = (string) Str::uuid();
        $this->dispatch('show-form', rubroId: $this->state['rubro_id'] ?? null, anexos: $this->state['rubros_anexos'] ?? []);
    }

    public function updateComercio()
    {
        // No dejamos que 'situacion' venga del formulario
        unset($this->state['situacion']);

        $estadoNorm = $this->normalizarEstado($this->state['estado'] ?? $this->ubicacion->estado ?? 'entramite');

        // ===== Reglas de validación
        $rules = [
            'persona_tipo'          => 'required|in:fisica,juridica',
            'apellido'              => 'nullable|string|min:2|max:60',
            'nombres'               => 'nullable|string|min:2|max:80',
            'razon_social'          => 'nullable|string|min:2|max:120',
            'dni_cuit'              => 'required|string',
            'rubro_id'              => 'required|exists:rubros,id',
            'rubros_anexos'         => 'array',
            'rubros_anexos.*'       => 'integer|exists:rubros,id|different:rubro_id|distinct',
            'domicilio_responsable' => 'nullable|string|min:3|max:160',
            'correo'                => 'nullable|email:rfc,dns|max:120',
            'nombre_comercial'      => 'nullable|string|min:2|max:120',
            'domicilio_comercio'    => 'nullable|string|min:3|max:160',
            'nomenclatura'          => 'nullable|string|max:80',
            'observaciones'         => 'nullable|string|max:500',
            'estado'                => 'required|in:entramite,vigente,irregular,baja,baja_oficio,sin_efecto',
            'tipo_hab'              => 'required|in:definitiva,prev',
            'fecha_alta'            => 'nullable|date',
            'fecha_baja'            => 'nullable|date',
            'fecha_vto'             => 'nullable|date',
            'documentos'            => 'array',
            'es_clausurado'         => 'boolean',
            // Repeaters
            'telefonos'               => 'array|min:1',
            'telefonos.*'             => ['nullable','regex:/^[\d\s()+\-]{6,20}$/'],
            'disposiciones'           => 'array',
            'disposiciones.*.numero'  => 'nullable|string|max:60',
            'disposiciones.*.fecha'   => 'nullable|date',
            'habilitaciones'          => 'array',
            'habilitaciones.*.numero' => 'nullable|string|max:60',
            'habilitaciones.*.fecha'  => 'nullable|date',
        ];

        // Condicionales por persona
        if (($this->state['persona_tipo'] ?? 'fisica') === 'fisica') {
            $rules['apellido'] = 'required|string|min:2|max:60';
            $rules['nombres']  = 'required|string|min:2|max:80';
        } else {
            $rules['razon_social'] = 'required|string|min:2|max:120';
        }

        // Condicionales por estado
        $prevNorm       = $this->normalizarEstado($this->ubicacion->getOriginal('estado') ?? $this->ubicacion->estado ?? 'entramite');
        $yaTeniaAlta    = !empty($this->ubicacion?->fecha_alta);
        $vieneAltaAhora = !empty($this->state['fecha_alta']);

        switch ($estadoNorm) {
            case 'vigente':
                if ($prevNorm === 'entramite' && !$yaTeniaAlta && !$vieneAltaAhora) {
                    $rules['fecha_alta'] = 'required|date';
                }
                break;
            case 'irregular':
                $rules['fecha_alta'] = 'required|date';
                break;
            case 'baja':
            case 'baja_oficio':
            case 'sin_efecto':
                $tieneAltaAntes = $yaTeniaAlta || $vieneAltaAhora;
                $rules['fecha_baja'] = 'required|date' . ($tieneAltaAntes ? '|after_or_equal:fecha_alta' : '') . '|before_or_equal:today';
                if (!$tieneAltaAntes) {
                    $rules['fecha_alta'] = 'required|date|before_or_equal:today';
                }
                break;
        }

        // Validar
        $validated = \Validator::make($this->state, $rules)->validate();

        // ===== Normalizar datos
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $c) {
            if (!empty($validated[$c] ?? null)) $validated[$c] = Str::title($validated[$c]);
        }
        $validated['dni_cuit'] = preg_replace('/\D/','', $validated['dni_cuit'] ?? '');

        // calcular situacion según estado y checkbox
        if (in_array('situacion', Schema::getColumnListing('ubicaciones'), true)) {
            $validated['situacion'] = $this->calcularSituacion($validated['estado'] ?? null, (bool)($validated['es_clausurado'] ?? false));
        }
        unset($validated['domicilio_responsable'], $validated['es_clausurado']); // no se persisten directo

        // Re-geocodificar si cambió la dirección
        $dirVieja = trim((string)$this->ubicacion->getOriginal('domicilio_comercio'));
        $dirNueva = trim((string)($validated['domicilio_comercio'] ?? ''));
        if ($dirNueva !== '' && $dirNueva !== $dirVieja) {
            $enricher = app(\App\Services\UbicacionGeoEnricher::class);
            $validated = $enricher->enrich($validated);
        }

        // Filtrar a columnas reales
        $colsUbic = Schema::getColumnListing('ubicaciones');
        $dataUbic = array_intersect_key($validated, array_flip($colsUbic));

        // Documentos provenientes del form
        $docsFromUI = $this->state['documentos'] ?? [];

        DB::transaction(function () use ($dataUbic, $docsFromUI) {
            // 1) Actualizar Ubicación
            $this->ubicacion->update($dataUbic);

            // 2) Documentos → normalizar + map select exclusivo de "uso de inmueble"
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

            // Filtrar columnas reales y limpiar claves peligrosas
            $colsDocs = Schema::getColumnListing('ubicacion_documentos');
            $nuevo    = array_intersect_key($incoming, array_flip($colsDocs));
            unset($nuevo['id'], $nuevo['created_at'], $nuevo['updated_at'], $nuevo['ubicacion_id']);

            // Merge con lo ya guardado para no perder tildes previas
            $actualBD = $this->ubicacion->documentos?->toArray() ?? [];
            $actual   = array_intersect_key($actualBD, array_flip($colsDocs));
            $payload  = array_merge($actual, $nuevo);

            // Guardar documentos (NO pasamos ubicacion_id en el payload)
            $this->ubicacion->documentos()->updateOrCreate(
                ['ubicacion_id' => $this->ubicacion->id],
                $payload
            );

            // 3) Rubros (principal + anexos)
            $principal = (int)($this->state['rubro_id'] ?? 0);
            $anexos = collect($this->state['rubros_anexos'] ?? [])
                ->map(fn($v)=>(int)$v)->filter()
                ->reject(fn($id)=>$id===$principal)->unique()->values()->all();

            $this->ubicacion->rubros()->sync(array_values(array_unique(array_merge([$principal], $anexos))));
            $this->ubicacion->rubro_id = $principal ?: null;
            $this->ubicacion->save();

            // 4) Teléfonos
            $this->ubicacion->telefonos()->delete();
            $telSan = collect($this->state['telefonos'] ?? [])
                ->map(fn($t)=>trim((string)$t))->filter()->unique()->values();
            foreach ($telSan as $t) {
                $this->ubicacion->telefonos()->create(['telefono'=>$t]);
            }

            // 5) Disposiciones
            $this->ubicacion->disposiciones()->delete();
            foreach (($this->state['disposiciones'] ?? []) as $d) {
                $num = trim((string)($d['numero'] ?? '')); if ($num === '') continue;
                $this->ubicacion->disposiciones()->create([
                    'numero' => $num,
                    'fecha'  => !empty($d['fecha']) ? $d['fecha'] : null,
                ]);
            }

            // 6) Habilitaciones
            $this->ubicacion->habilitaciones()->delete();
            foreach (($this->state['habilitaciones'] ?? []) as $h) {
                $num = trim((string)($h['numero'] ?? '')); if ($num === '') continue;
                $this->ubicacion->habilitaciones()->create([
                    'numero' => $num,
                    'fecha'  => !empty($h['fecha']) ? $h['fecha'] : null,
                ]);
            }
        });

        // Notificar UI y refrescar datos mínimos
        $this->dispatch('ubicacion-actualizada', id: $this->ubicacion->id);

        $this->ubicacion->refresh()->load('documentos','rubros','telefonos','disposiciones','habilitaciones');
        $this->state['persona_tipo'] = $this->ubicacion->persona_tipo ?? 'fisica';
        $this->state['estado']       = $this->normalizarEstado($this->ubicacion->estado ?? 'entramite');

        $this->dispatch('hide-form', ['message' => 'Registro actualizado correctamente']);
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
        if (!is_array($tels) || count($tels) <= 1) return; // dejar al menos uno
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
        $estado = $this->normalizarEstado($nuevo);
        $esJuridica = ($this->state['persona_tipo'] ?? 'fisica') === 'juridica';
        $permitidos = $this->docKeysForEstado($estado, $esJuridica);

        $docs = $this->state['documentos'] ?? [];
        foreach (array_keys($this->docLabels) as $k) $docs[$k] = false; // apago todo
        foreach ($permitidos as $k) $docs[$k] = (bool)($docs[$k] ?? false); // preservo del estado
        $docs['doc_uso_inmueble_tipo'] = null; // reset select
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

        // Estado inicial mínimo
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

    /** ====== Prefill desde mapa (opcionales) ====== */
    #[On('open-create-from-map')]
    public function openCreateFromMap(?string $direccion = null, ?string $barrio = null, ?string $nomen = null): void
    {
        $this->state['domicilio_comercio'] = $direccion ?: '';
        $this->state['nomenclatura']       = $nomen ?: '';
        $this->state['barrio']             = $barrio ?: '';

        $this->dispatch('show-form',
            rubroId: ($this->state['rubro_id'] ?? null),
            anexos:  ($this->state['rubros_anexos'] ?? [])
        );

        $this->dispatch('refresh-selects',
            rubroId: ($this->state['rubro_id'] ?? null),
            anexos:  ($this->state['rubros_anexos'] ?? [])
        );
    }

    #[On('prefill-desde-mapa')]
    public function prefillDesdeMapa($direccion = null, $barrio = null, $nomenclatura = null)
    {
        $this->state['domicilio_comercio'] = $direccion ?? '';
        $this->state['barrio']             = $barrio ?? '';
        $this->state['nomenclatura']       = $nomenclatura ?? '';
        $this->dispatch('show-form', rubroId: ($this->state['rubro_id'] ?? null), anexos: ($this->state['rubros_anexos'] ?? []));
    }

    public function render()
    {
        $this->ubicacion->loadMissing('rubros','telefonos');

        // Docs desde BD normalizados
        $docsDB = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $docs   = $this->normalizeDocsArray($docsDB);

        // Schema dinámico
        $schema = $this->docSchema;

        // Conteo para badge
        $total = count($schema['items']);
        $presentadas = 0;
        foreach ($schema['items'] as $it) {
            if (!empty($docs[$it['key']])) $presentadas++;
        }

        $historial = $this->ubicacion->movimientos()->get()->keyBy('etapa');
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
        ])->layout('admin.layouts.app');
    }
}
