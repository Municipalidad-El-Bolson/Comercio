<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index() {
        $users = User::orderBy('name')->paginate(12);
        return view('admin.users.index', compact('users'));
    }

    public function create() {
        $roles = ['admin' => 'Administrador', 'writer' => 'Escritor', 'reader' => 'Lector', 'mesa' => 'Mesa de entrada'];
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:8','confirmed'],
            'role'     => ['required','in:admin,writer,reader,mesa'],
        ]);

        // NO Hash::make(): el cast 'hashed' lo hace solo
        User::create($data);

        return redirect()->route('admin.users.index')->with('ok','Usuario creado.');
    }
}
