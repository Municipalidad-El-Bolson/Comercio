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

#[Layout('admin.layouts.mesa')]
class Form extends Component
{
    public string $fecha = '';
    public ?int $nro_ingreso = null;
    public string $titular_razon = '';
    public ?string $hc = null;
    public array $documentacion_ids = [];

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
    }

    public function selectAll(): void
    {
        $this->documentacion_ids = $this->opsDocs->pluck('id')->map(fn($v) => (int)$v)->all();
    }

    public function clearAll(): void
    {
        $this->documentacion_ids = [];
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
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $docs = array_values($this->selectedDocsMap);

        $payload = [
            'fecha'       => $this->fecha,
            'nro_ingreso' => $this->nro_ingreso,
            'docs'        => $docs,
            'titular'     => $this->titular_razon,
            'hc'          => $this->hc,
            'sender_name' => auth()->user()->name ?? 'Mesa de Entrada',
        ];

        // 🔹 Buscar usuarios destino
        $destinatarios = User::query()
            ->where('id', '!=', auth()->id())
            ->whereIn('role', ['admin', 'writer', 'reader'])
            ->get();

        if ($destinatarios->count() > 0) {
            Notification::send($destinatarios, new MesaEntradaNotification($payload));
        }

        // 🔹 Limpieza total del formulario
        $this->resetErrorBag();
        $this->resetValidation();
        $this->nro_ingreso = null;
        $this->titular_razon = '';
        $this->hc = null;
        $this->documentacion_ids = [];
        $this->fecha = Carbon::today()->format('Y-m-d');

        // 🔹 Mostrar mensaje
        session()->flash('status', '✅ Notificación enviada correctamente.');

        // 🔹 Forzar refresco del componente (para limpiar inputs visualmente)
        $this->dispatch('form-reset');
    }

    public function render()
    {
        return view('livewire.mesa-entrada.form', [
            'opsDocs' => $this->opsDocs,
            'selectedDocsMap' => $this->selectedDocsMap,
        ]);
    }
}
