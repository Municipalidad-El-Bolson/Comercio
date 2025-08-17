<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    protected $fillable = [
        'ubicacion_id',
        'tipo',         // 'timeline' | 'acta'
        'titulo',
        'descripcion',
        'etapa',
        'fecha',
        'estado',
        'archivo',
        'observacion',
    ];

    public function ubicacion()
    {
        return $this->belongsTo(\App\Models\Ubicacion::class);
    }
}
