<?php

namespace App\Livewire\Comercio;

use Livewire\Component;
use App\Models\Ubicacion;
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
        // Asegurá que exista el array
        $this->state['documentos'] = $this->state['documentos'] ?? [];

        // Setear todas las claves conocidas
        foreach ($this->docDefaults as $k => $def) {
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

        // Pasar modelo a array y fusionar docs con defaults
        $this->state = $this->ubicacion->toArray();
        $docs = $this->ubicacion->documentos ? $this->ubicacion->documentos->toArray() : [];
        $soloDocs = array_intersect_key($docs, $this->docDefaults);
        $this->state['documentos'] = array_merge($this->docDefaults, $soloDocs);

        $this->dispatch('show-form'); // abre el modal
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

        $documentos = $validated['documentos'] ?? [];
        unset($validated['documentos']);

        // update ubicacion
        $this->ubicacion->update($validated);

        // upsert documentos
        $permitidos = array_intersect_key($documentos, $this->docDefaults);
        $this->ubicacion->documentos()
            ->updateOrCreate(['ubicacion_id' => $this->ubicacion->id], $permitidos);

        $this->dispatch('hide-form', ['message' => 'Registro actualizado correctamente']);
    }

    public function render()
    {
        $historial = $this->ubicacion
            ->movimientos()
            ->get()
            ->keyBy('etapa'); // o el campo que uses como clave

        return view('livewire.comercio.comercio-data', [
            'ubicacion' => $this->ubicacion,
            'historial' => $historial,
            'rubros' => $this->rubros
        ])->layout('admin.layouts.app');
    }
}
