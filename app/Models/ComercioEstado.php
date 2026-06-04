<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComercioEstado extends Model
{

    protected $table = 'comercio_estados';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo','nombre',
        'aplica_fecha_alta','aplica_fecha_baja','aplica_fecha_vto','habilita_seguimiento',
        'orden',
    ];
}
