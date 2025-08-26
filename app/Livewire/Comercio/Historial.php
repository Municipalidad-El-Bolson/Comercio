<?php

namespace App\Livewire\Comercio;

use Livewire\Attributes\Layout;
use App\Models\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('admin.layouts.app')]
class Historial extends Component
{
    use WithPagination;

    // Filtros
    public string $search = '';
    public ?string $desde = null;
    public ?string $hasta = null;
    public ?string $adminName = null; // <-- nombre del admin

    protected $queryString = ['search', 'desde', 'hasta', 'adminName'];
    protected $paginationTheme = 'bootstrap';

    public function updating($field)
    {
        if (in_array($field, ['search','desde','hasta','adminName'])) {
            $this->resetPage();
        }
    }

    public function filtrar()
    {
        $this->resetPage();
    }

    public function render()
    {
        $q = AuditLog::with('user')->latest();

        if ($this->search !== '') {
            $q->where(function($w){
                $w->where('action', 'like', "%{$this->search}%")
                  ->orWhere('entity_type', 'like', "%{$this->search}%")
                  ->orWhere('path', 'like', "%{$this->search}%");
            });
        }

        if ($this->desde) $q->where('created_at', '>=', $this->desde.' 00:00:00');
        if ($this->hasta) $q->where('created_at', '<=', $this->hasta.' 23:59:59');

        if ($this->adminName) {
            $q->whereHas('user', fn($u) =>
                $u->where('name', 'like', "%{$this->adminName}%")
            );
        }

        return view('livewire.comercio.historial', [
            'items' => $q->paginate(15),
        ]);
    }
}

