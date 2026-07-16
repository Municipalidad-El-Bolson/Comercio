<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

#[Layout('admin.layouts.app')]
class UsersIndex extends Component
{
    use WithPagination, AuthorizesRequests;

    public string $search = '';
    public int $perPage = 10;
    public string $sortField = 'name';
    public string $sortDir = 'asc';

    // modal/form
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $role = 'reader';
    public string $password = '';
    public string $password_confirmation = '';

    public array $roleOptions = [
        ['value' => 'admin',  'label' => 'Administrador'],
        ['value' => 'writer', 'label' => 'Usuario administrativo'],
        ['value' => 'reader', 'label' => 'Inspector'],
        ['value' => 'mesa',   'label' => 'Mesa de entrada'],
    ];

    protected $queryString = ['search' => ['except' => '']];

    public function mount(): void
    {
        $this->authorize('access-admin'); // gate que ya definiste
    }

    public function updatingSearch() { $this->resetPage(); }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'asc';
        }
    }

    public function rules(): array
    {
        $roles = array_column($this->roleOptions, 'value');

        $base = [
            'name'  => ['required','string','max:255'],
            'email' => [
                'required','email','max:255',
                Rule::unique('users','email')->ignore($this->editingId),
            ],
            'role'  => ['required', Rule::in($roles)],
        ];

        // Crear: password requerido | Editar: password opcional
        if ($this->editingId) {
            $base['password'] = ['nullable','confirmed', Password::defaults()];
        } else {
            $base['password'] = ['required','confirmed', Password::defaults()];
        }

        return $base;
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $u = User::findOrFail($id);
        $this->editingId = $u->id;
        $this->name = $u->name;
        $this->email = $u->email;
        $this->role = $u->role;
        $this->password = '';
        $this->password_confirmation = '';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->name = preg_replace('/\s+/', ' ', trim($this->name));
        $this->email = mb_strtolower(trim($this->email));
        $this->validate();

        DB::transaction(function (): void {
            if ($this->editingId) {
                $u = User::lockForUpdate()->findOrFail($this->editingId);
                if ($u->role === 'admin' && $this->role !== 'admin' && User::where('role', 'admin')->count() <= 1) {
                    throw ValidationException::withMessages(['role' => 'Debe quedar al menos un administrador.']);
                }
                $u->name = $this->name;
                $u->email = $this->email;
                $u->role = $this->role;
                if ($this->password !== '') $u->password = Hash::make($this->password);
                $u->save();
            } else {
                User::create([
                    'name' => $this->name, 'email' => $this->email, 'role' => $this->role,
                    'password' => Hash::make($this->password),
                ]);
            }
        });

        session()->flash('status', $this->editingId ? 'Usuario actualizado correctamente.' : 'Usuario creado correctamente.');
        $this->showForm = false;
        $this->dispatch('autosave-clear');
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('access-admin');
        // Evitar que un admin se elimine a sí mismo si querés:
        if (auth()->id() === $id) {
            session()->flash('status', 'No podés eliminar tu propio usuario.');
            return;
        }
        $usuario = User::findOrFail($id);
        if ($usuario->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            session()->flash('error', 'No se puede eliminar el último administrador.');
            return;
        }
        $usuario->delete();
        session()->flash('status', 'Usuario eliminado.');
        $this->resetPage();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId','name','email','role','password','password_confirmation'
        ]);
        $this->role = 'reader';
        $this->resetValidation();
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function render()
    {
        $search = $this->search; // ← guardarlo antes

        $users = User::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);

        return view('livewire.admin.users-index', compact('users'));
    }

}
