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
        $this->ubicacion = $ubicacion->loadMissing('documentos', 'rubro');

        $this->state = $this->ubicacion->toArray();

        $this->state['estado'] = trim(mb_strtolower($this->state['estado'] ?? 'entramite'));

        $this->rehidratarRubrosDesde($this->ubicacion->rubro_id ?: null);

        $docsRaw = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $docs = $this->normalizeDocsArray($docsRaw);
        $this->state['documentos'] = array_merge($this->docDefaults, $docs);


        $this->dispatch('show-form');
    }

    public function updateComercio()
    {
        // NO mandamos 'situacion': la setea el modelo en saving()
        unset($this->state['situacion']);

        // Estado normalizado actual y anterior
        $estadoNorm   = $this->normalizarEstado($this->state['estado'] ?? $this->ubicacion->estado ?? 'entramite');
        $prevNorm     = $this->normalizarEstado($this->ubicacion->getOriginal('estado') ?? $this->ubicacion->estado ?? 'entramite');

        $yaTeniaAlta      = !empty($this->ubicacion?->fecha_alta);
        $vieneAltaAhora   = !empty($this->state['fecha_alta']);

        // Reglas base
        $rules = [
            'persona_tipo'          => 'required|in:fisica,juridica',
            'apellido'              => 'nullable|string|min:2|max:60',
            'nombres'               => 'nullable|string|min:2|max:80',
            'razon_social'          => 'nullable|string|min:2|max:120',
            'dni_cuit'              => 'required|string',
            'rubro_id'              => 'required|exists:rubros,id',
            'domicilio_responsable' => 'required|string|min:3|max:160',
            'correo'                => 'nullable|email:rfc,dns|max:120',
            'telefono'              => 'nullable|regex:/^[\d\s()+\-]{6,20}$/',
            'nombre_comercial'      => 'nullable|string|min:2|max:120',
            'domicilio_comercio'    => 'nullable|string|min:3|max:160', // si usás required_without:nomenclatura, agregalo
            'nomenclatura'          => 'nullable|string|max:80',
            'observaciones'         => 'nullable|string|max:500',
            'estado'                => 'required|in:entramite,vigente,irregular,baja',
            'tipo_hab'              => 'required|in:definitiva,prev',
            'fecha_alta'            => 'nullable|date',
            'fecha_baja'            => 'nullable|date',
            'documentos'            => 'array',
        ];

        // Condicionales por persona
        if (($this->state['persona_tipo'] ?? 'fisica') === 'fisica') {
            $rules['apellido'] = 'required|string|min:2|max:60';
            $rules['nombres']  = 'required|string|min:2|max:80';
        } else {
            $rules['razon_social'] = 'required|string|min:2|max:120';
        }

        // ===== Reglas de fechas "a prueba de balas" =====
        switch ($estadoNorm) {
            case 'entramite':
                // No forzamos fechas
                break;

            case 'vigente':
                // Sólo exigimos fecha_alta si venís desde 'entramite' y no había ni hay fecha_alta
                if ($prevNorm === 'entramite' && !$yaTeniaAlta && !$vieneAltaAhora) {
                    $rules['fecha_alta'] = 'required|date';
                }
                break;

            case 'irregular':
                // Irregular siempre necesita fecha de alta
                $rules['fecha_alta'] = 'required|date';
                break;

            case 'baja':
                $tieneAltaAntes = $yaTeniaAlta || $vieneAltaAhora;

                // Baja siempre requiere fecha_baja
                $rules['fecha_baja'] = 'required|date' . ($tieneAltaAntes ? '|after_or_equal:fecha_alta' : '') . '|before_or_equal:today';

                // Si no había alta ni ahora tampoco, exigila (para consistencia histórica)
                if (!$tieneAltaAntes) {
                    $rules['fecha_alta'] = 'required|date|before_or_equal:today';
                }
                break;
        }

        // Validar
        $validated = \Illuminate\Support\Facades\Validator::make($this->state, $rules)->validate();

        // Normalizaciones cosmeticas
        foreach (['razon_social','apellido','nombres','domicilio_responsable','nombre_comercial','domicilio_comercio'] as $c) {
            if (!empty($validated[$c])) $validated[$c] = \Illuminate\Support\Str::title($validated[$c]);
        }

        // Documentos
        $documentos = $this->normalizeDocsArray($validated['documentos'] ?? []);
        unset($validated['documentos']);

        // NO seteamos 'situacion' a mano; la calcula el modelo en saving()
        // Guardar Ubicación
        $this->ubicacion->update($validated);

        // Guardar checklist (crea si no existe)
        $cols = \Illuminate\Support\Facades\Schema::getColumnListing('ubicacion_documentos');
        $payload = array_intersect_key($documentos, array_flip($cols));
        $this->ubicacion->documentos()->updateOrCreate(
            ['ubicacion_id' => $this->ubicacion->id],
            $payload + ['ubicacion_id' => $this->ubicacion->id]
        );

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
