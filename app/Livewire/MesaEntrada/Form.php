<?php

namespace App\Livewire\MesaEntrada;

use App\Models\User;
use App\Models\Documento;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Notifications\MesaEntradaNotification;

#[Layout('admin.layouts.mesa')]
class Form extends Component
{
    public string $fecha;
    public ?int $nro_ingreso = null;
    public string $titular_razon = '';
    public ?string $hc = null;

    /** IDs seleccionados (checkboxes) */
    public array $documentacion_ids = [];

    /** Catálogo (Collection de Documento con id/nombre) */
    public $opsDocs = [];

    public function mount(): void
    {
        abort_unless(Gate::allows('mesa-entrada-send'), 403);
        $this->fecha = Carbon::today()->format('Y-m-d');
        $this->loadDocs();
    }

    protected function loadDocs(): void
    {
        $this->opsDocs = Documento::where('activo', true)
            ->orderBy('nombre')
            ->get(['id','nombre']);
    }

    /**
     * Propiedad computada: arma el mapa [id => nombre] de lo seleccionado.
     * Livewire la expone como $this->selectedDocsMap.
     */
    public function getSelectedDocsMapProperty(): array
    {
        $ids = array_map('intval', $this->documentacion_ids);
        if (empty($ids)) {
            return [];
        }

        return Documento::whereIn('id', $ids)
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->toArray();
    }

    /** Quitar uno desde la (x) del chip */
    public function removeDoc(int $id): void
    {
        $this->documentacion_ids = array_values(array_filter(
            $this->documentacion_ids,
            fn ($v) => (int)$v !== (int)$id
        ));
        // No hace falta recalcular: la propiedad computada se actualiza sola
    }

    /** Tildar todos */
    public function selectAll(): void
    {
        $this->documentacion_ids = $this->opsDocs
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /** Destildar todo */
    public function clearAll(): void
    {
        $this->documentacion_ids = [];
    }

    public function rules(): array
    {
        return [
            'fecha'                 => ['required','date'],
            'nro_ingreso'           => ['required','integer','min:1'],
            'documentacion_ids'     => ['array','min:1'],
            'documentacion_ids.*'   => ['integer', Rule::exists('documentos','id')],
            'titular_razon'         => ['required','string','max:255'],
            'hc'                    => ['nullable','string','max:100'],
        ];
    }

    public function submit(): void
    {
        $this->validate();

        // Nombres legibles de los documentos seleccionados (desde la prop computada)
        $docs = array_values($this->selectedDocsMap);

        $payload = [
            'fecha'       => $this->fecha,
            'nro_ingreso' => $this->nro_ingreso,
            'docs'        => $docs,
            'titular'     => $this->titular_razon,
            'hc'          => $this->hc,
            'sender_name' => auth()->user()->name ?? null,
        ];

        // Notificar a admin/writer/reader (excluyendo al emisor)
        $destinatarios = User::query()
            ->where('id', '!=', auth()->id())
            ->whereIn('role', ['admin','writer','reader'])
            ->get();

        foreach ($destinatarios as $u) {
            $u->notify(new MesaEntradaNotification($payload));
        }

        session()->flash('status', 'Notificación enviada a los usuarios.');

        // Reset (la prop computada se vacía sola al limpiar documentacion_ids)
        $this->reset(['nro_ingreso','documentacion_ids','titular_razon','hc']);
        $this->fecha = now()->format('Y-m-d');
    }

    public function render()
    {
        return view('livewire.mesa-entrada.form', [
            'opsDocs'         => $this->opsDocs,
            'selectedDocsMap' => $this->selectedDocsMap, // <- propiedad computada
        ]);
    }
}
