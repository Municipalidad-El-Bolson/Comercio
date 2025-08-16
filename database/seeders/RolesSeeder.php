<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Permisos finos (puedes ampliar cuando quieras)
        $perms = [
            'ubicaciones.create',
            'ubicaciones.update',
            'ubicaciones.delete',
            'movimientos.create',
            'historial.view',
        ];

        foreach ($perms as $p) { Permission::findOrCreate($p); }

        // Roles
        $admin     = Role::findOrCreate('admin');
        $cargador  = Role::findOrCreate('cargador');   // puede cargar/editar comercios
        $operador  = Role::findOrCreate('operador');   // no edita comercios; sí carga movimientos

        // Asignar permisos a roles
        $admin->givePermissionTo(Permission::all());

        $cargador->givePermissionTo([
            'ubicaciones.create','ubicaciones.update','movimientos.create'
        ]);

        $operador->givePermissionTo([
            'movimientos.create'
        ]);
    }
}

