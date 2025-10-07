<?php

return [
    'entities' => [
        \App\Models\Movimiento::class => ['label' => 'Movimiento', 'gender' => 'm'],
        \App\Models\Ubicacion::class  => ['label' => 'Ubicación', 'gender' => 'f'],
        \App\Models\Comercio::class   => ['label' => 'Comercio',   'gender' => 'm'],
        \App\Models\User::class       => ['label' => 'Usuario',     'gender' => 'm'],
    ],


     'routes' => [
        'login.post'         => 'Inicio de sesión',
        'logout'             => 'Cierre de sesión',
        'ubicaciones.store'  => 'Se creó una Ubicación',
        'ubicaciones.update' => 'Se modificó una Ubicación',
        'ubicaciones.destroy'=> 'Se eliminó una Ubicación',
        'movimientos.store'  => 'Se creó un Acta',
        'movimientos.update' => 'Se modificó un Acta',
    ],

    // Paths internos que conviene ocultar del subtítulo
    'internal_paths' => [
        'livewire/update',
        'sanctum/csrf-cookie',
        '_ignition',
    ],

    // Mapeo de campos por entidad (y comodín *)
    'fields' => [
        \App\Models\Ubicacion::class => [
            'persona_tipo'          => 'Tipo de persona',
            'apellido'              => 'Apellido',
            'nombres'               => 'Nombres',
            'razon_social'          => 'Razón social',
            'dni_cuit'              => 'DNI / CUIT',
            'rubro_id'              => 'Rubro',
            'domicilio_responsable' => 'Domicilio del responsable',
            'correo'                => 'Correo electrónico',
            'telefono'              => 'Teléfono',
            'nombre_comercial'      => 'Nombre comercial',
            'domicilio_comercio'    => 'Domicilio del comercio',
            'nomenclatura'          => 'Nomenclatura catastral',
            'lat'                   => 'Latitud',
            'lng'                   => 'Longitud',
            'barrio'                => 'Barrio',
            'cpu_cod'               => 'Código CPU',
            'cpu_nombre'            => 'Nombre CPU',
            'observaciones'         => 'Observaciones',
            'estado_base'           => 'Estado base',
            'estado_label'          => 'Etiqueta de estado',
            'estado'                => 'Estado',
            'situacion'             => 'Situación',
            'tipo_hab'              => 'Tipo de habilitación',
            'fecha_alta'            => 'Fecha de alta',
            'fecha_baja'            => 'Fecha de baja',
            'fecha_vto'             => 'Fecha de vencimiento',
            'monto_pagar'           => 'Monto a pagar',
        ],
        \App\Models\Movimiento::class => [
        'tipo'   => 'Tipo de acta',
        'titulo' => 'Título',
        'texto'  => 'Descripción',
        'estado' => 'Estado',
        'fecha'  => 'Fecha',
        ],
        \App\Models\User::class => [
            'name'  => 'Nombre',
            'email' => 'Correo',
        ],
        '*' => [
            'created_at' => 'Creado el',
            'updated_at' => 'Actualizado el',
            'deleted_at' => 'Eliminado el',
            'status'     => 'Estado',
        ],
         
    ],
];
