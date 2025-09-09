<?php

namespace App\Livewire\Comercio;

use Livewire\Component;
use App\Models\Ubicacion;
use App\Models\UbicacionDocumento;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\Rubro;

class ComercioData extends Component
{
    public Ubicacion $ubicacion;

    public $showEditModal = false;
    public $rubros;
    public $state = [];
    public array $megas = [];
    public array $madres = [];
    public array $subs = [];
    public string $selectedMega = '';
    public string $selectedMadre = '';

    public array $madresOptions = [];   // índice = fila, valor = array de "rubro_madre"
    public array $subsOptions   = [];   // índice = fila, valor = array de ['id'=>..., 'sub'=>...]

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

    /** Labels visibles (centralizamos en el componente) */
    public array $labelsGenerales = [
        'doc_libre_deuda_municipal' => 'Certificado de libre deuda municipal',
        'doc_planeamiento_urbano' => 'Dirección de Planeamiento Urbano',
        'doc_solicitud_habilitacion_pago' => 'Solicitud de habilitación + pago',
        'doc_comprobante_uso_local' => 'Comprobante de uso del local',
        'doc_afip_constancia' => 'Constancia de inscripción AFIP',
        'doc_recaudacion_rn' => 'Constancia de inscripción Agencia Recaudación RN',
        'doc_fotocopia_dni' => 'Fotocopia de DNI',
        'doc_comprobante_uso_inmueble' => 'Comprobante de uso del inmueble',
        'doc_libre_deuda_tasas_inmueble' => 'Libre deuda de tasas del inmueble',
        'doc_aptitud_tecnica_local' => 'Certificado de aptitud técnica',
        'doc_cocap_rhi' => 'Certificado CO.CA.P.RHI',
        'doc_nota_carteleria_obras' => 'Nota a Obras por cartelería',
        'doc_libro_actas_100' => 'Libro de actas (100 hojas)',
    ];


    public array $labelsJuridicas = [
        'doc_acta_constitucion' => 'Acta de constitución',
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

    public function marcarTodosLosDocs(bool $valor = true): void
    {
        $this->state['documentos'] = $this->state['documentos'] ?? [];
        foreach (array_keys($this->docDefaults) as $k) {
            // Si es persona física, no marcar los 3 de jurídica
            if (($this->state['persona_tipo'] ?? $this->ubicacion->persona_tipo ?? 'fisica') === 'fisica' &&
            in_array($k, ['doc_acta_constitucion','doc_contrato_societario','doc_docs_representantes'], true)) {
            $this->state['documentos'][$k] = false;
            continue;
            }
            $this->state['documentos'][$k] = $valor;
        }
    }

    public function mount(Ubicacion $ubicacion)
    {
        $this->ubicacion = $ubicacion->load('rubro', 'documentos', 'movimientos');
        $this->rubros = Rubro::select('id', 'rubro_madre', 'subrubro')
            ->orderBy('rubro_madre')
            ->orderBy('subrubro')
            ->get();

        $this->megas = Rubro::query()
            ->select('mega_rubro')->distinct()->orderBy('mega_rubro')
            ->pluck('mega_rubro')->toArray();

        $this->rehidratarRubrosDesde($this->ubicacion->rubro_id);
    }

    private function rehidratarRubrosDesde(?int $rubroId): void
    {
        if (!$rubroId) {
            $this->selectedMega = '';
            $this->selectedMadre = '';
            $this->madres = [];
            $this->subs = [];
            $this->state['rubro_id'] = null;
            return;
        }

        $r = Rubro::find($rubroId);
        if (!$r) {
            $this->rehidratarRubrosDesde(null);
            return;
        }

        $this->selectedMega = $r->mega_rubro ?? '';
        $this->madres = Rubro::where('mega_rubro', $this->selectedMega)
            ->select('rubro_madre')->distinct()->orderBy('rubro_madre')->pluck('rubro_madre')->toArray();

        $this->selectedMadre = $r->rubro_madre ?? '';
        $this->subs = Rubro::where('mega_rubro', $this->selectedMega)
            ->where('rubro_madre', $this->selectedMadre)
            ->orderBy('subrubro')
            ->get(['id','subrubro'])
            ->map(fn($x)=>['id'=>$x->id,'sub'=>$x->subrubro])
            ->toArray();

        $this->state['rubro_id'] = (int) $rubroId;
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


    public function editaComercio(Ubicacion $ubicacion)
    {
        $this->showEditModal = true;

        // Cargar relaciones necesarias (incluye múltiples)
        $this->ubicacion = $ubicacion->loadMissing([
            'rubro',            // compat legado
            'rubros',           // many-to-many
            'documentos',
            'telefonos',        // 1-N
            'disposiciones',    // 1-N
            'habilitaciones',   // 1-N
            'movimientos',      // si tu vista los usa
        ]);

        // Base de state desde el modelo
        $this->state = $this->ubicacion->toArray();

        // Fechas a Y-m-d para inputs
        foreach (['fecha_alta','fecha_baja','fecha_vto'] as $f) {
            $this->state[$f] = !empty($this->ubicacion->{$f})
                ? $this->ubicacion->{$f}->format('Y-m-d')
                : null;
        }

        // Normalizar estado
        $this->state['estado'] = $this->normalizarEstado($this->state['estado'] ?? $this->ubicacion->estado ?? 'entramite');

        // =======================
        // RUBROS (MÚLTIPLES)
        // =======================
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


        // Fallback si no hay en pivot: usar rubro_id legado o fila vacía
        if (empty($this->state['rubros'])) {
            if ($this->ubicacion->rubro_id) {
                $r = \App\Models\Rubro::find($this->ubicacion->rubro_id);
                $this->state['rubros'] = [[
                    'mega'   => $r?->mega_rubro ?? '',
                    'madre'  => $r?->rubro_madre ?? '',
                    'sub_id' => $r?->id ?? null,
                ]];
            } else {
                $this->state['rubros'] = [['mega'=>'','madre'=>'','sub_id'=>null]];
            }
        }


        // Compatibilidad con tus selects legados (single): hidratar desde rubro_id
        $this->rehidratarRubrosDesde($this->ubicacion->rubro_id ?: null);

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
        $docsRaw = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $docs = $this->normalizeDocsArray($docsRaw);
        $this->state['documentos'] = array_merge($this->docDefaults, $docs);

        // Mostrar modal
        $this->dispatch('show-form');
    }

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


    public function updateComercio()
    {
        // No mandamos 'situacion': la calcula el modelo en saving()
        unset($this->state['situacion']);

        // Compat: si vienen rubros múltiples, setear rubro_id = primer sub_id (para reglas legacy)
        if (empty($this->state['rubro_id'] ?? null)) {
            $firstSub = collect($this->state['rubros'] ?? [])->pluck('sub_id')->filter()->first();
            if ($firstSub) $this->state['rubro_id'] = (int)$firstSub;
        }

        // Estado normalizado actual y anterior
        $estadoNorm = $this->normalizarEstado($this->state['estado'] ?? $this->ubicacion->estado ?? 'entramite');
        $prevNorm   = $this->normalizarEstado($this->ubicacion->getOriginal('estado') ?? $this->ubicacion->estado ?? 'entramite');

        $yaTeniaAlta    = !empty($this->ubicacion?->fecha_alta);
        $vieneAltaAhora = !empty($this->state['fecha_alta']);

        // ===== Reglas base (en this->state, no con prefijo state.) =====
        $rules = [
            'persona_tipo'          => 'required|in:fisica,juridica',
            'apellido'              => 'nullable|string|min:2|max:60',
            'nombres'               => 'nullable|string|min:2|max:80',
            'razon_social'          => 'nullable|string|min:2|max:120',
            'dni_cuit'              => 'required|string',
            'rubro_id'              => 'required|exists:rubros,id',
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
            'fecha_vto'             => 'nullable|date',   // vencimiento manual
            'documentos'            => 'array',

            // ===== Punto 3.5: repeaters =====
            'telefonos'               => 'array|min:1',
            'telefonos.*'             => ['nullable','regex:/^[\d\s()+\-]{6,20}$/'],

            'rubros'                  => 'array|min:1',
            'rubros.*.sub_id'         => 'required|exists:rubros,id',

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

        // Reglas de fechas por estado
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

        // Validar contra $this->state
        $validated = \Validator::make($this->state, $rules)->validate();

        // ===== Normalizaciones =====
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $c) {
            if (!empty($validated[$c])) $validated[$c] = \Illuminate\Support\Str::title($validated[$c]);
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

        // ===== Persistencia de repeaters =====

        // RUBROS (pivot) + compat rubro_id
        $rubrosIds = collect($this->state['rubros'] ?? [])
            ->pluck('sub_id')->filter()->unique()->values()->all();
        $this->ubicacion->rubros()->sync($rubrosIds);
        $this->ubicacion->rubro_id = $rubrosIds[0] ?? null; // compat para vistas legadas
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

        // Avisar (ej: re-centrar mapa si corresponde)
        $this->dispatch('ubicacion-actualizada', id: $this->ubicacion->id);

        // Cerrar modal
        $this->dispatch('hide-form', ['message' => 'Registro actualizado correctamente']);
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

    private function normalizeDocsArray(array $docs): array
    {
    // Asegurar booleanos
    $docs = array_map(static fn($v) => (bool)$v, $docs);


    // AFIP (unificamos)
    if (array_key_exists('doc_afip_constancia_fisica', $docs) || array_key_exists('doc_afip_constancia_juridica', $docs)) {
    $docs['doc_afip_constancia'] = (bool)($docs['doc_afip_constancia_fisica'] ?? false)
    || (bool)($docs['doc_afip_constancia_juridica'] ?? false);
    }


    // Recaudación RN (unificamos)
    if (array_key_exists('doc_constancia_recaudacion', $docs)) {
    $docs['doc_recaudacion_rn'] = (bool)$docs['doc_constancia_recaudacion'];
    }


    // Filtrar sólo las claves canónicas soportadas
    return array_intersect_key($docs, $this->docDefaults);
    }

    public function render()
    {
        // Datos para la vista (sin PHP pesado en Blade)
        $esJuridica = ($this->ubicacion->persona_tipo ?? 'fisica') === 'juridica';
        $labels = $esJuridica ? ($this->labelsGenerales + $this->labelsJuridicas) : $this->labelsGenerales;
        $docsDB = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $docs = $this->normalizeDocsArray($docsDB);


        $total = count($labels);
        $presentadas = 0;
        foreach (array_keys($labels) as $k) {
            if (!empty($docs[$k])) $presentadas++;
        }


        $historial = $this->ubicacion->movimientos()->get()->keyBy('etapa');


        return view('livewire.comercio.comercio-data', [
            'ubicacion' => $this->ubicacion,
            'historial' => $historial,
            'rubros' => $this->rubros,
            'megas'  => $this->megas,
            'madres' => $this->madres,
            'subs'   => $this->subs,
            // Documentación
            'docs' => $docs,
            'labelsGenerales' => $this->labelsGenerales,
            'labelsJuridicas' => $this->labelsJuridicas,
            'esJuridica' => $esJuridica,
            'docsTotal' => $total,
            'docsOK' => $presentadas,
        ])->layout('admin.layouts.app');
    }
}
