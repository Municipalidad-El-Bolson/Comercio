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
    /** Campos del formulario */
    public string $fecha;
    public ?int $nro_ingreso = null;
    public string $titular_razon = '';
    public ?string $hc = null;

    /** Documentación seleccionable */
    public array $documentacion_ids = [];   // IDs tildados (checkboxes)
    public array $selectedDocsMap   = [];   // [id => nombre] para chips

    /** Catálogo */
    public $opsDocs = [];                   // Collection de Documento (id, nombre)

    /** Dropdown + búsqueda */
    public bool $docsOpen = false;          // abrir/cerrar dropdown
    public string $docsQuery = '';          // texto de búsqueda

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

        $this->mapSelected();
    }

    /** Propiedad computada: lista filtrada por texto */
    public function getFilteredDocsProperty()
    {
        if ($this->docsQuery === '') {
            return $this->opsDocs;
        }

        $q = mb_strtolower($this->docsQuery);
        return $this->opsDocs->filter(function ($d) use ($q) {
            return str_contains(mb_strtolower($d->nombre), $q);
        });
    }

    /** Se dispara al tildar/destildar */
    public function updatedDocumentacionIds(): void
    {
        $this->mapSelected();
    }

    /** Reconstruye el mapa id=>nombre para chips */
    protected function mapSelected(): void
    {
        if (empty($this->documentacion_ids)) {
            $this->selectedDocsMap = [];
            return;
        }

        $this->selectedDocsMap = Documento::whereIn('id', $this->documentacion_ids)
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

        $this->mapSelected();
    }

    /** Tildar todos los visibles del filtro actual */
    public function selectAllVisible(): void
    {
        $ids = $this->filteredDocs->pluck('id')->map(fn($v) => (int)$v)->all();
        $this->documentacion_ids = array_values(array_unique(array_merge($this->documentacion_ids, $ids)));
        $this->mapSelected();
    }

    /** Destildar todo */
    public function clearAll(): void
    {
        $this->documentacion_ids = [];
        $this->mapSelected();
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

        // Nombres legibles de los documentos seleccionados
        $docs = array_values($this->selectedDocsMap);

        $payload = [
            'fecha'       => $this->fecha,
            'nro_ingreso' => $this->nro_ingreso,
            'docs'        => $docs,
            'titular'     => $this->titular_razon,
            'hc'          => $this->hc,
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

        // Reset del form
        $this->reset(['nro_ingreso','documentacion_ids','titular_razon','hc','docsQuery']);
        $this->selectedDocsMap = [];
        $this->fecha = now()->format('Y-m-d');
        $this->docsOpen = false;
    }

    public function render()
    {
        return view('livewire.mesa-entrada.form', [
            'opsDocs'          => $this->opsDocs,
            'selectedDocsMap'  => $this->selectedDocsMap,
        ]);
    }
}
