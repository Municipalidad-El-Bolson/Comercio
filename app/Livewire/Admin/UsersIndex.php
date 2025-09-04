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
        ['value' => 'writer', 'label' => 'Escritor'],
        ['value' => 'reader', 'label' => 'Lector'],
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
        $this->dispatch('open-user-modal');
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
        $this->dispatch('open-user-modal');
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $u = User::findOrFail($this->editingId);
            $u->name = $this->name;
            $u->email = $this->email;
            $u->role = $this->role;
            if ($this->password !== '') {
                $u->password = Hash::make($this->password);
            }
            $u->save();
            session()->flash('status', 'Usuario actualizado.');
        } else {
            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'password' => Hash::make($this->password),
            ]);
            session()->flash('status', 'Usuario creado.');
        }

        $this->dispatch('close-user-modal');
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
        User::findOrFail($id)->delete();
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

    public function render()
    {
        $users = User::query()
            ->when($this->search, fn($q) =>
                $q->where(function($q){
                    $q->where('name','like',"%{$this->search}%")
                      ->orWhere('email','like',"%{$this->search}%")
                      ->orWhere('role','like',"%{$this->search}%");
                })
            )
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);

        return view('livewire.admin.users-index', compact('users'))->layout('admin.layouts.app');;
    }
}
