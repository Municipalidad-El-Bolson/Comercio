<?php

namespace App\Livewire\Comercio;

use Livewire\Component;
use App\Models\Ubicacion;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;
use App\Models\Rubro;
use Livewire\Attributes\On;

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

    /** Marcar/limpiar checklist documentos */
    public function marcarTodosLosDocs(bool $valor = true): void
    {
        $this->state['documentos'] = $this->state['documentos'] ?? [];
        foreach (array_keys($this->docDefaults) as $k) {
            // Si es persona física, no marcar los 3 de jurídica
            if (($this->state['persona_tipo'] ?? $this->ubicacion->persona_tipo ?? 'fisica') === 'fisica'
                && in_array($k, ['doc_acta_constitucion','doc_contrato_societario','doc_docs_representantes'], true)) {
                $this->state['documentos'][$k] = false;
                continue;
            }
            $this->state['documentos'][$k] = $valor;
        }
    }

    /** ====== Ciclo de vida ====== */
    public function mount(Ubicacion $ubicacion)
    {
        $this->ubicacion = $ubicacion->load('rubro', 'rubros', 'documentos', 'movimientos', 'telefonos', 'disposiciones', 'habilitaciones');

        // Opciones de rubros (solo subrubro + id)
        $this->rubros = Rubro::select('id','subrubro')
            ->orderBy('subrubro')
            ->get();

        // Estado inicial mínimo (para ver detalle antes de abrir modal)
        $this->state = [
            'rubro_id'       => $this->ubicacion->rubro_id,
            'rubros_anexos'  => [], // se completa al editar
        ];

        $this->rubroOpts = Rubro::orderBy('subrubro')
            ->limit(50)->get(['id','subrubro'])->toArray();
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
        }
    }

    /** ====== Editar modal ====== */
    public function editaComercio(Ubicacion $ubicacion)
    {
        $this->showEditModal = true;

        // Relaciones necesarias para el form
        $this->ubicacion = $ubicacion->loadMissing([
            'rubro',            // principal (legacy/actual)
            'rubros',           // many-to-many (para anexos + principal incluido)
            'documentos',
            'telefonos',
            'disposiciones',
            'habilitaciones',
            'movimientos',
        ]);

        // Base del state
        $this->state = $this->ubicacion->toArray();

        // Fechas en Y-m-d para inputs
        foreach (['fecha_alta','fecha_baja','fecha_vto'] as $f) {
            $this->state[$f] = !empty($this->ubicacion->{$f})
                ? $this->ubicacion->{$f}->format('Y-m-d')
                : null;
        }

        // Normalizar estado
        $this->state['estado'] = $this->normalizarEstado($this->state['estado'] ?? $this->ubicacion->estado ?? 'entramite');

        // ===== Rubro principal + anexos =====
        // Orden por 'orden' en pivot (si existe) para respetar prioridad
        $idsOrdenados = $this->ubicacion->rubros
            ->map(fn($r) => ['id' => $r->id, 'orden' => $r->pivot->orden ?? 9999])
            ->sortBy('orden')
            ->pluck('id')
            ->values()
            ->all();

        $principal = $this->ubicacion->rubro_id ?: ($idsOrdenados[0] ?? null);
        $anexos = collect($idsOrdenados)->filter(fn($id) => $id !== $principal)->values()->all();

        $this->state['rubro_id']      = $principal;
        $this->state['rubros_anexos'] = $anexos;

        // ===== Teléfonos =====
        $tels = $this->ubicacion->telefonos->pluck('telefono')->filter()->values()->all();
        $this->state['telefonos'] = !empty($tels) ? $tels : [''];

        // ===== Disposiciones =====
        $this->state['disposiciones'] = $this->ubicacion->disposiciones->map(function($d){
            return [
                'numero' => (string)$d->numero,
                'fecha'  => $d->fecha ? $d->fecha->format('Y-m-d') : null,
            ];
        })->values()->all();
        if (empty($this->state['disposiciones'])) {
            $this->state['disposiciones'] = [['numero'=>'','fecha'=>null]];
        }

        // ===== Habilitaciones =====
        $this->state['habilitaciones'] = $this->ubicacion->habilitaciones->map(function($h){
            return [
                'numero' => (string)$h->numero,
                'fecha'  => $h->fecha ? $h->fecha->format('Y-m-d') : null,
            ];
        })->values()->all();
        if (empty($this->state['habilitaciones'])) {
            $this->state['habilitaciones'] = [['numero'=>'','fecha'=>null]];
        }

        // ===== Documentos (checklist) =====
        $docsRaw = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $docs    = $this->normalizeDocsArray($docsRaw);
        $this->state['documentos'] = array_merge($this->docDefaults, $docs);
        
        $idsNecesarios = array_values(array_unique(array_filter(array_merge([$principal], $anexos))));
        $seleccionados = Rubro::whereIn('id', $idsNecesarios)->get(['id','subrubro'])->toArray();

        // merge sin duplicados por id (preferimos mantener el orden actual y anexar faltantes)
        $this->rubroOpts = $this->mergeOpts($this->rubroOpts, $seleccionados);
        $this->anexoOpts = $this->mergeOpts($this->anexoOpts, $seleccionados);

        $this->formKey = (string) Str::uuid();
        $this->dispatch('show-form', rubroId: ($this->state['rubro_id'] ?? null), anexos: ($this->state['rubros_anexos'] ?? []));
    }

    /** ====== Guardar cambios ====== */
    public function updateComercio()
    {
        unset($this->state['situacion']); // la calcula el modelo

        // Normalizaciones de estado
        $estadoNorm = $this->normalizarEstado($this->state['estado'] ?? $this->ubicacion->estado ?? 'entramite');
        $prevNorm   = $this->normalizarEstado($this->ubicacion->getOriginal('estado') ?? $this->ubicacion->estado ?? 'entramite');

        $yaTeniaAlta    = !empty($this->ubicacion?->fecha_alta);
        $vieneAltaAhora = !empty($this->state['fecha_alta']);

        // ===== Reglas =====
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
            'estado'                => 'required|in:entramite,vigente,irregular,baja',
            'tipo_hab'              => 'required|in:definitiva,prev',
            'fecha_alta'            => 'nullable|date',
            'fecha_baja'            => 'nullable|date',
            'fecha_vto'             => 'nullable|date',
            'documentos'            => 'array',

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

        // Reglas por estado
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
                $tieneAltaAntes = $yaTeniaAlta || $vieneAltaAhora;
                $rules['fecha_baja'] = 'required|date' . ($tieneAltaAntes ? '|after_or_equal:fecha_alta' : '') . '|before_or_equal:today';
                if (!$tieneAltaAntes) {
                    $rules['fecha_alta'] = 'required|date|before_or_equal:today';
                }
                break;
        }

        // Validar
        $validated = \Validator::make($this->state, $rules)->validate();

        // ===== Normalizaciones =====
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $c) {
            if (!empty($validated[$c])) $validated[$c] = Str::title($validated[$c]);
        }
        $validated['dni_cuit'] = preg_replace('/\D/','', $validated['dni_cuit'] ?? '');
        unset($validated['domicilio_responsable'], $validated['nomenclatura']);

        // Re-geocodificar si cambió la dirección
        $dirVieja = trim((string)$this->ubicacion->getOriginal('domicilio_comercio'));
        $dirNueva = trim((string)($validated['domicilio_comercio'] ?? ''));
        if ($dirNueva !== '' && $dirNueva !== $dirVieja) {
            $enricher = app(\App\Services\UbicacionGeoEnricher::class);
            $validated = $enricher->enrich($validated);
        }

        // Documentos (normalizados a claves canónicas)
        $documentos = $this->normalizeDocsArray($validated['documentos'] ?? []);
        unset($validated['documentos']);

        // ===== Guardar Ubicación =====
        $this->ubicacion->update($validated);

        // Guardar checklist
        $cols = \Schema::getColumnListing('ubicacion_documentos');
        $payload = array_intersect_key($documentos, array_flip($cols));
        $this->ubicacion->documentos()->updateOrCreate(
            ['ubicacion_id' => $this->ubicacion->id],
            $payload + ['ubicacion_id' => $this->ubicacion->id]
        );

        // ===== Rubro principal + anexos (pivot) =====
        $principal = (int)($this->state['rubro_id'] ?? 0);
        $anexos = collect($this->state['rubros_anexos'] ?? [])
            ->map(fn($v)=>(int)$v)
            ->filter()
            ->reject(fn($id)=>$id === $principal)
            ->unique()
            ->values()
            ->all();

        $pivotIds = array_values(array_unique(array_merge([$principal], $anexos)));
        $this->ubicacion->rubros()->sync($pivotIds);
        $this->ubicacion->rubro_id = $principal ?: null; // compat
        $this->ubicacion->save();

        // ===== Teléfonos =====
        $this->ubicacion->telefonos()->delete();
        $telSan = collect($this->state['telefonos'] ?? [])
            ->map(fn($t)=>trim((string)$t))
            ->filter(fn($t)=>$t !== '')
            ->unique()
            ->values();
        foreach ($telSan as $t) {
            $this->ubicacion->telefonos()->create(['telefono'=>$t]);
        }

        // ===== Disposiciones =====
        $this->ubicacion->disposiciones()->delete();
        foreach (($this->state['disposiciones'] ?? []) as $d) {
            $num = trim((string)($d['numero'] ?? ''));
            if ($num === '') continue;
            $this->ubicacion->disposiciones()->create([
                'numero' => $num,
                'fecha'  => !empty($d['fecha']) ? $d['fecha'] : null,
            ]);
        }

        // ===== Habilitaciones =====
        $this->ubicacion->habilitaciones()->delete();
        foreach (($this->state['habilitaciones'] ?? []) as $h) {
            $num = trim((string)($h['numero'] ?? ''));
            if ($num === '') continue;
            $this->ubicacion->habilitaciones()->create([
                'numero' => $num,
                'fecha'  => !empty($h['fecha']) ? $h['fecha'] : null,
            ]);
        }

        // Notificar UI
        $this->dispatch('ubicacion-actualizada', id: $this->ubicacion->id);
        $this->dispatch('hide-form', ['message' => 'Registro actualizado correctamente']);
    }

    /** ====== Helpers ====== */
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

    private function normalizeDocsArray(array $docs): array
    {
        // Asegurar booleanos
        $docs = array_map(static fn($v) => (bool)$v, $docs);

        // AFIP (unificamos)
        if (array_key_exists('doc_afip_constancia_fisica', $docs) || array_key_exists('doc_afip_constancia_juridica', $docs)) {
            $docs['doc_afip_constancia'] =
                (bool)($docs['doc_afip_constancia_fisica'] ?? false)
                || (bool)($docs['doc_afip_constancia_juridica'] ?? false);
        }

        // Recaudación RN (unificamos)
        if (array_key_exists('doc_constancia_recaudacion', $docs)) {
            $docs['doc_recaudacion_rn'] = (bool)$docs['doc_constancia_recaudacion'];
        }

        // Sólo claves soportadas
        return array_intersect_key($docs, $this->docDefaults);
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

    private function mergeOpts(array $opts, array $extra): array
    {
        $byId = [];
        foreach ($opts as $op) $byId[$op['id']] = $op;
        foreach ($extra as $op) $byId[$op['id']] = $op; // sobreescribe/añade
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


    public function render()
    {
        $this->ubicacion->loadMissing('rubros','telefonos');

        $esJuridica = ($this->ubicacion->persona_tipo ?? 'fisica') === 'juridica';
        $labels = $esJuridica ? ($this->labelsGenerales + $this->labelsJuridicas) : $this->labelsGenerales;

        $docsDB = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $docs   = $this->normalizeDocsArray($docsDB);

        $total = count($labels);
        $presentadas = 0;
        foreach (array_keys($labels) as $k) if (!empty($docs[$k])) $presentadas++;

        $historial = $this->ubicacion->movimientos()->get()->keyBy('etapa');

        return view('livewire.comercio.comercio-data', [
            'ubicacion'  => $this->ubicacion,
            'historial'  => $historial,
            'rubros'     => $this->rubros,
            'docs'       => $docs,
            'labelsGenerales' => $this->labelsGenerales,
            'labelsJuridicas' => $this->labelsJuridicas,
            'esJuridica' => $esJuridica,
            'docsTotal'  => $total,
            'docsOK'     => $presentadas,
        ])->layout('admin.layouts.app');
    }
}
