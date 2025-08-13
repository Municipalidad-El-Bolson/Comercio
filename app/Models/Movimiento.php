<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    protected $fillable = ['ubicacion_id', 'titulo', 'descripcion', 'estado', 'archivo'];

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class);
    }
}
