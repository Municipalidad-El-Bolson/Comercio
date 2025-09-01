<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AuditsModelChanges;

class Movimiento extends Model
{
    use AuditsModelChanges;

    public const ACTA_TIPOS = ['asesoramiento','notificacion','inspeccion','infraccion'];
    
    protected $fillable = [
        'ubicacion_id',
        'tipo',
        'tipo_acta',
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

    public $timestamps = true;
    
    public function ubicacion()
    {
        return $this->belongsTo(\App\Models\Ubicacion::class);
    }
}
