<?php

namespace App\Livewire\Comercio;

use Livewire\Attributes\Layout;
use App\Models\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Comercio;
use App\Models\Movimiento;

#[Layout('admin.layouts.app')]
class Historial extends Component
{
    use WithPagination;

    public string $userName = '';     // Nombre de Usuario (con sugerencias)
    public ?string $objeto = null;    // 'comercio' | 'acta'
    public ?string $accion = null;    // 'crear' | 'editar' | 'loguear'
    public ?string $desde  = null;
    public ?string $hasta  = null;

    public array $userHints = [];

        protected $queryString = [
        'userName' => ['except' => ''],
        'objeto'   => ['except' => null],
        'accion'   => ['except' => null],
        'desde'    => ['except' => null],
        'hasta'    => ['except' => null],
    ];

    protected $paginationTheme = 'bootstrap';

    public function updating($field)
    {
        if (in_array($field, ['userName','objeto','accion','desde','hasta'])) {
            $this->resetPage();
        }
    }

    public function updatedUserName()
    {
        $q = trim($this->userName);
        $this->userHints = $q && mb_strlen($q) >= 2
            ? User::where('name', 'like', $q.'%')
                ->orderBy('name')
                ->limit(8)
                ->pluck('name')
                ->all()
            : [];
    }

    
    public function clearFilters(): void
    {
        $this->reset(['userName','objeto','accion','desde','hasta']);
        $this->resetPage();
    }

    public function render()
    {
        $q = AuditLog::query()
            ->latest()
            ->with('user')
            ->with(['entity' => function (\Illuminate\Database\Eloquent\Relations\MorphTo $morphTo) {
                $morphTo->morphWith([
                    \App\Models\Ubicacion::class => [], // por si mostraste nombre_fantasia
                    \App\Models\Movimiento::class => [],
                ]);
            }]);

        // Filtro: Nombre de Usuario (texto con sugerencias)
        if ($this->userName !== '') {
            $needle = "%{$this->userName}%";
            $q->whereHas('user', fn($u) => $u->where('name', 'like', $needle));
        }

        // Filtro: Objeto (Comercio | Acta)
        if ($this->objeto) {
            $map = [
                'comercio' => Ubicacion::class,
                'acta'     => Movimiento::class,
            ];
            if (isset($map[$this->objeto])) {
                $q->where('entity_type', $map[$this->objeto]);
            }
        }

        // Filtro: Acción (crear | editar | loguear)
        if ($this->accion) {
            $accion = $this->accion;

            $q->where(function($w) use ($accion) {
                switch ($accion) {
                    case 'crear':
                        // meta->action = created  (y fallback por texto)
                        $w->where('meta->action', 'created')
                          ->orWhere('action', 'like', 'Se creó%');
                        break;

                    case 'editar':
                        $w->where('meta->action', 'updated')
                          ->orWhere('action', 'like', 'Se modificó%')
                          ->orWhere('action', 'like', 'Se actualizó%');
                        break;

                    case 'loguear':
                        // logs de autenticación (nombre de ruta o path/login)
                        $w->where('meta->route', 'login')
                          ->orWhere('action', 'login')
                          ->orWhere('action', 'login.post')
                          ->orWhere('path', 'like', '%login%');
                        break;
                }
            });
        }

        // Filtro: fechas
        if ($this->desde) $q->where('created_at', '>=', $this->desde.' 00:00:00');
        if ($this->hasta) $q->where('created_at', '<=', $this->hasta.' 23:59:59');

        // Sugerencias de usuarios (para el datalist)
        $this->updatedUserName();

        return view('livewire.comercio.historial', [
            'items'     => $q->paginate(15),
            'userHints' => $this->userHints,
        ]);
    }
}

