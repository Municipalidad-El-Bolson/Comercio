<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    protected $fillable = [
        'ubicacion_id',
        'tipo',
        'titulo',
        'descripcion',
        'estado',
        'archivo',
        'etapa',
        'fecha',
        'observacion',
    ];

    protected $casts = [
        'fecha'       => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function ubicacion()
    {
        return $this->belongsTo(\App\Models\Ubicacion::class);
    }
}
