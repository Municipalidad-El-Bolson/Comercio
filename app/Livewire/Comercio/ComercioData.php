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
    }

    public function editaComercio(Ubicacion $ubicacion)
    {
        $this->showEditModal = true;
        $this->ubicacion = $ubicacion->loadMissing('documentos');

        $this->state = $this->ubicacion->toArray();

        $docsRaw = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $docs = $this->normalizeDocsArray($docsRaw);
        $this->state['documentos'] = array_merge($this->docDefaults, $docs);


        $this->dispatch('show-form');
    }

    public function updateComercio()
    {
        $rules = [
            'persona_tipo'          => 'required|in:fisica,juridica',
            'apellido'              => 'nullable|string',
            'nombres'               => 'nullable|string',
            'razon_social'          => 'nullable|string',
            'dni_cuit'              => 'required|string',
            'rubro_id'              => 'required|exists:rubros,id',
            'domicilio_responsable' => 'required|string',
            'correo'                => 'nullable|email',
            'telefono'              => 'nullable|string',
            'nombre_comercial'      => 'nullable|string',
            'domicilio_comercio'    => 'required|string',
            'nomenclatura'          => 'nullable|string',
            'observaciones'         => 'nullable|string',
            'estado'                => 'required|in:vigente,irregular,entramite',
            'situacion'             => 'required|in:alta,baja',
            'fecha_alta'            => 'nullable|date',
            'fecha_baja'            => 'nullable|date',
            'documentos'            => 'array',
        ];

        // Reglas condicionales
        if (($this->state['persona_tipo'] ?? 'fisica') === 'fisica') {
            $rules['apellido'] = 'required|string';
            $rules['nombres']  = 'required|string';
        } else {
            $rules['razon_social'] = 'required|string';
        }
        if (($this->state['situacion'] ?? 'alta') === 'baja') {
            $rules['fecha_baja'] = 'required|date';
        } else {
            $rules['fecha_alta'] = 'required|date';
        }

        $validated = Validator::make($this->state, $rules)->validate();

        foreach (['razon_social', 'apellido', 'nombres', 'domicilio_responsable', 'nombre_comercial', 'domicilio_comercio'] as $campo) {
            if (!empty($validated[$campo])) {
                $validated[$campo] = Str::title($validated[$campo]);
            }
        }

        $documentos = $this->normalizeDocsArray($validated['documentos'] ?? []);
        unset($validated['documentos']);

        
        $cols = Schema::getColumnListing('ubicacion_documentos');
        $payload = array_intersect_key($documentos, array_flip($cols));

        $this->ubicacion->documentos()->updateOrCreate(
            ['ubicacion_id' => $this->ubicacion->id],
            $payload + ['ubicacion_id' => $this->ubicacion->id]
        );

        $this->dispatch('hide-form', ['message' => 'Registro actualizado correctamente']);
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
