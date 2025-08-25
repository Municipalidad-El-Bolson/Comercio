<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('admin.layouts.app')]
class RegisterUser extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'reader';

    public array $roleOptions = [
        ['value' => 'admin',  'label' => 'Admin'],
        ['value' => 'writer', 'label' => 'Writer'],
        ['value' => 'reader', 'label' => 'Reader'],
    ];

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role'     => ['required', Rule::in(array_column($this->roleOptions, 'value'))],
        ];
    }

    public function submit(): void
    {
        $this->validate();

        User::create([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => Hash::make($this->password),
            'role'     => $this->role,
        ]);

        session()->flash('status', 'Usuario registrado correctamente.');
        $this->reset(['name','email','password','password_confirmation']);
        $this->role = 'reader';
    }

    public function render()
    {
        return view('livewire.auth.register-user');
    }
}
