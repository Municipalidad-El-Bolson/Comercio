<?php

namespace App\Livewire\MesaEntrada;

use App\Models\User;
use App\Models\Documento;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Notification;
use App\Notifications\MesaEntradaNotification;
use App\Models\MesaEntradaRegistro;

#[Layout('admin.layouts.mesa')]
class Form extends Component
{
    public string $fecha = '';
    public ?int $nro_ingreso = null;
    public string $titular_razon = '';
    public ?string $hc = null;
    public array $documentacion_ids = [];
    public array $documentacion_nombres = [];
    public string $observacion = '';

    /** Documentos disponibles */
    public $opsDocs = [];

    public function mount(): void
    {
        abort_unless(Gate::allows('mesa-entrada-send'), 403);

        $this->fecha = Carbon::today()->format('Y-m-d');
        $this->loadDocs();
    }

    protected function loadDocs(): void
    {
        $this->opsDocs = Documento::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }

    /** Propiedad computada: docs seleccionados con nombre */
    public function getSelectedDocsMapProperty(): array
    {
        if (empty($this->documentacion_ids)) {
            return [];
        }

        return Documento::whereIn('id', $this->documentacion_ids)
            ->pluck('nombre', 'id')
            ->toArray();
    }

    /** Quitar uno desde la X */
    public function removeDoc(int $id): void
    {
        $this->documentacion_ids = array_values(
            array_filter($this->documentacion_ids, fn($v) => (int)$v !== (int)$id)
        );
        unset($this->documentacion_nombres[$id]);
    }

    public function updatedDocumentacionIds(): void
    {
        $seleccionados = array_map('intval', $this->documentacion_ids);
        foreach ($this->opsDocs as $documento) {
            if (in_array((int) $documento->id, $seleccionados, true) && empty($this->documentacion_nombres[$documento->id])) {
                $this->documentacion_nombres[$documento->id] = $documento->nombre;
            }
        }
        $this->documentacion_nombres = array_intersect_key($this->documentacion_nombres, array_flip($seleccionados));
    }

    public function selectAll(): void
    {
        $this->documentacion_ids = $this->opsDocs->pluck('id')->map(fn($v) => (int)$v)->all();
        $this->updatedDocumentacionIds();
    }

    public function clearAll(): void
    {
        $this->documentacion_ids = [];
        $this->documentacion_nombres = [];
    }

    public function rules(): array
    {
        return [
            'fecha'               => ['required', 'date'],
            'nro_ingreso'         => ['required', 'integer', 'min:1'],
            'titular_razon'       => ['required', 'string', 'max:255'],
            'hc'                  => ['nullable', 'string', 'max:100'],
            'documentacion_ids'   => ['array', 'min:1'],
            'documentacion_ids.*' => ['integer', Rule::exists('documentos', 'id')],
            'documentacion_nombres.*' => ['required', 'string', 'max:255'],
            'observacion' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $docs = collect($this->documentacion_ids)
            ->map(fn ($id) => trim((string) ($this->documentacion_nombres[(int) $id] ?? $this->selectedDocsMap[(int) $id] ?? '')))
            ->filter()->values()->all();

        $payload = [
            'fecha'       => $this->fecha,
            'nro_ingreso' => $this->nro_ingreso,
            'docs'        => $docs,
            'titular'     => $this->titular_razon,
            'hc'          => $this->hc,
            'sender_name' => auth()->user()->name ?? 'Mesa de Entrada',
            'observacion' => trim($this->observacion) ?: null,
        ];

        $registro = MesaEntradaRegistro::create([
            'fecha' => $this->fecha,
            'nro_ingreso' => $this->nro_ingreso,
            'titular_razon' => $this->titular_razon,
            'hc' => $this->hc,
            'documentos' => $docs,
            'observacion' => $payload['observacion'],
            'user_id' => auth()->id(),
            'sender_name' => $payload['sender_name'],
        ]);

        $payload['registro_id'] = $registro->id;

        // 🔹 Buscar usuarios destino
        $destinatarios = User::query()
            ->where('id', '!=', auth()->id())
            ->whereIn('role', ['admin', 'writer', 'reader'])
            ->get();

        if ($destinatarios->count() > 0) {
            Notification::send($destinatarios, new MesaEntradaNotification($payload));
        }

        $this->resetErrorBag();
        $this->resetValidation();
        $this->reset([
            'nro_ingreso',
            'titular_razon',
            'hc',
            'documentacion_ids',
            'documentacion_nombres',
            'observacion',
        ]);

        $this->loadDocs();

        $this->fecha = Carbon::today()->format('Y-m-d');

        session()->flash('status', ' Notificación enviada correctamente.');

        $this->dispatch('$refresh');
    }

    public function render()
    {
        return view('livewire.mesa-entrada.form', [
            'opsDocs' => $this->opsDocs,
            'selectedDocsMap' => $this->selectedDocsMap,
        ]);
    }
}
