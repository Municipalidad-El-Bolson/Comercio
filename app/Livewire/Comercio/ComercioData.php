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

    /** ====== Documentación ====== */
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
        'doc_comprobante_uso_inmueble' => 'Comprobante de uso del inmueble',
        'doc_libre_deuda_tasas_inmueble' => 'Libre deuda de tasas municipales',
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
            'en tramite','en trámite','en_tramite','en-tramite' => 'entramite',
            'vigente' => 'vigente',
            'irregular' => 'irregular',
            'baja' => 'baja',
            default => 'entramite',
        };
    }

    private function normalizeDocsArray(array $docs): array
    {
        // claves textuales que NO booleaneamos
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

        // ✅ NO pisa: toma lo que haya guardado + alternativos
        $out['doc_afip_constancia'] =
            (bool)($out['doc_afip_constancia'] ?? false)               // ya unificado en BD
        || (bool)($out['doc_afip_constancia_fisica'] ?? false)        // legacy física
        || (bool)($out['doc_afip_constancia_juridica'] ?? false);     // legacy jurídica

        return $out;
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
        // reglas (las mismas que venías usando; simplifico aquí)
        $rules = [
            'persona_tipo' => 'required|in:fisica,juridica',
            'dni_cuit'     => 'required|string',
            'rubro_id'     => 'required|exists:rubros,id',
            'estado'       => 'required|in:entramite,vigente,irregular,baja',
            'documentos'   => 'array',
            // repeaters
            'telefonos'               => 'array|min:1',
            'telefonos.*'             => ['nullable','regex:/^[\d\s()+\-]{6,20}$/'],
            'disposiciones'           => 'array',
            'disposiciones.*.numero'  => 'nullable|string|max:60',
            'disposiciones.*.fecha'   => 'nullable|date',
            'habilitaciones'          => 'array',
            'habilitaciones.*.numero' => 'nullable|string|max:60',
            'habilitaciones.*.fecha'  => 'nullable|date',
        ];
        // persona
        if (($this->state['persona_tipo'] ?? 'fisica') === 'fisica') {
            $rules['apellido'] = 'required|string|min:2|max:60';
            $rules['nombres']  = 'required|string|min:2|max:80';
        } else {
            $rules['razon_social'] = 'required|string|min:2|max:120';
        }

        $validated = \Validator::make($this->state, $rules)->validate();

        // normalizar y enriquecer
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $c) {
            if (!empty($validated[$c] ?? null)) $validated[$c] = Str::title($validated[$c]);
        }
        $validated['dni_cuit'] = preg_replace('/\D/','', $validated['dni_cuit'] ?? '');
        unset($validated['domicilio_responsable'], $validated['nomenclatura']);

        $dirVieja = trim((string)$this->ubicacion->getOriginal('domicilio_comercio'));
        $dirNueva = trim((string)($validated['domicilio_comercio'] ?? ''));
        if ($dirNueva !== '' && $dirNueva !== $dirVieja) {
            $enricher = app(\App\Services\UbicacionGeoEnricher::class);
            $validated = $enricher->enrich($validated);
        }

        DB::transaction(function () use ($validated) {
            // 1) actualizar Ubicación
            $docsFromUI = $this->state['documentos'] ?? [];
            unset($validated['documentos']);
            $this->ubicacion->update($validated);

            // 2) docs: normalizar + map select exclusivo
            $incoming = $this->normalizeDocsArray($docsFromUI);
            // flags de uso: reset
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
            $tipo = $incoming['doc_uso_inmueble_tipo'] ?? null;
            if ($tipo && isset($map[$tipo])) $incoming[$map[$tipo]] = true;

            // merge con BD para no perder checks viejos
            $actualBD = $this->ubicacion->documentos?->toArray() ?? [];
            $cols = Schema::getColumnListing('ubicacion_documentos');
            $actualFiltrado = array_intersect_key($actualBD, array_flip($cols));
            $nuevoFiltrado  = array_intersect_key($incoming, array_flip($cols));
            $payload = array_merge($actualFiltrado, $nuevoFiltrado);
            $payload['ubicacion_id'] = $this->ubicacion->id;

            $this->ubicacion->documentos()->updateOrCreate(
                ['ubicacion_id' => $this->ubicacion->id],
                $payload
            );

            // 3) rubros
            $principal = (int)($this->state['rubro_id'] ?? 0);
            $anexos = collect($this->state['rubros_anexos'] ?? [])
                ->map(fn($v)=>(int)$v)->filter()->reject(fn($id)=>$id===$principal)->unique()->values()->all();
            $this->ubicacion->rubros()->sync(array_values(array_unique(array_merge([$principal], $anexos))));
            $this->ubicacion->rubro_id = $principal ?: null;
            $this->ubicacion->save();

            // 4) teléfonos
            $this->ubicacion->telefonos()->delete();
            $telSan = collect($this->state['telefonos'] ?? [])
                ->map(fn($t)=>trim((string)$t))->filter()->unique()->values();
            foreach ($telSan as $t) $this->ubicacion->telefonos()->create(['telefono'=>$t]);

            // 5) disposiciones
            $this->ubicacion->disposiciones()->delete();
            foreach (($this->state['disposiciones'] ?? []) as $d) {
                $num = trim((string)($d['numero'] ?? '')); if ($num === '') continue;
                $this->ubicacion->disposiciones()->create([
                    'numero'=>$num,
                    'fecha'=>!empty($d['fecha']) ? $d['fecha'] : null
                ]);
            }

            // 6) habilitaciones
            $this->ubicacion->habilitaciones()->delete();
            foreach (($this->state['habilitaciones'] ?? []) as $h) {
                $num = trim((string)($h['numero'] ?? '')); if ($num === '') continue;
                $this->ubicacion->habilitaciones()->create([
                    'numero'=>$num,
                    'fecha'=>!empty($h['fecha']) ? $h['fecha'] : null
                ]);
            }
        });

        // refrescar UI
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
            if ($it['type'] === 'checkbox') {
                $docs[$it['key']] = $valor;
            }
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
        foreach ($permitidos as $k) $docs[$k] = (bool)($docs[$k] ?? false); // preservo los del estado
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

        // Estado inicial mínimo
        $this->state = [
            'persona_tipo'  => $this->ubicacion->persona_tipo ?? 'fisica',
            'estado'        => $this->normalizarEstado($this->ubicacion->estado ?? 'entramite'),
            'rubro_id'      => $this->ubicacion->rubro_id,
            'rubros_anexos' => [],
        ];

        $this->anexoOpts = $this->rubroOpts;
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

    /** ====== Nuevos desde mapa (opcional) ====== */
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
