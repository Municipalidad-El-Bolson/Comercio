<?php

namespace Database\Seeders;

// database/seeders/UsuariosInicialesSeeder.php
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UsuariosInicialesSeeder extends Seeder
{
    public function run(): void
    {
        // Permisos base
        $perms = [
            'ubicaciones.create','ubicaciones.update','ubicaciones.delete',
            'movimientos.create','historial.view',
        ];
        foreach ($perms as $p) { Permission::findOrCreate($p); }

        // Roles
        $admin    = Role::findOrCreate('admin');
        $cargador = Role::findOrCreate('cargador');
        $operador = Role::findOrCreate('operador');

        // Asignación de permisos a roles
        $admin->givePermissionTo(Permission::all());
        $cargador->givePermissionTo(['ubicaciones.create','ubicaciones.update','movimientos.create']);
        $operador->givePermissionTo(['movimientos.create']); // cualquiera podrá si querés (ver opción B antes)

        // Usuarios de ejemplo
        $uAdmin = User::firstOrCreate(
            ['email' => 'admin@demo.local'],
            ['name' => 'Admin', 'password' => Hash::make('password')]
        ); $uAdmin->assignRole('admin');

        $uCarg = User::firstOrCreate(
            ['email' => 'cargador@demo.local'],
            ['name' => 'Cargador', 'password' => Hash::make('password')]
        ); $uCarg->assignRole('cargador');

        $uOper = User::firstOrCreate(
            ['email' => 'operador@demo.local'],
            ['name' => 'Operador', 'password' => Hash::make('password')]
        ); $uOper->assignRole('operador');
    }
}
