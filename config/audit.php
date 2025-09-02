<?php

return [
    // Mapeo de nombres “humanos” de entidades
    'entities' => [
        App\Models\Movimiento::class => ['label' => 'Movimiento', 'gender' => 'm'],
        App\Models\Ubicacion::class  => ['label' => 'Ubicación', 'gender' => 'f'],
        App\Models\Comercio::class => 'Comercio',
        App\Models\User::class     => 'Usuario',
        // ...
    ],

     'routes' => [
        'login'       => 'Inicio de sesión',
        'login.post'  => 'Inicio de sesión',
        'logout'      => 'Cierre de sesión',
        'password.email'  => 'Solicitud de restablecimiento de contraseña',
        'password.update' => 'Cambio de contraseña',
        // agrega lo que uses
    ],

    // Paths internos que conviene ocultar del subtítulo
    'internal_paths' => [
        'livewire/update',
        'sanctum/csrf-cookie',
    ],

    // Mapeo de campos por entidad (y comodín *)
    'fields' => [
        App\Models\Comercio::class => [
            'razon_social' => 'Razón social',
            'direccion'    => 'Dirección',
            'rubro'        => 'Rubro',
            'latitud'      => 'Latitud',
            'longitud'     => 'Longitud',
            'tipo'         => 'Tipo',
            'estado'       => 'Estado',
        ],
        App\Models\User::class => [
            'name'  => 'Nombre',
            'email' => 'Correo',
        ],
        '*' => [ // fallback global
            'created_at' => 'Creado el',
            'updated_at' => 'Actualizado el',
            'deleted_at' => 'Eliminado el',
            'status'     => 'Estado',
        ],
    ],
];
