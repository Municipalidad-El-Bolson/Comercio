<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UbicacionEstadoHist extends Model
{
    public function setFechaAltaAttribute($v){ $this->attributes['fecha_alta'] = $v ?: null; }
    public function setFechaBajaAttribute($v){ $this->attributes['fecha_baja'] = $v ?: null; }
    public function setFechaVtoAttribute($v) { $this->attributes['fecha_vto']  = $v ?: null; }

    protected $table = 'ubicacion_estado_historial';

    protected $fillable = [
        'ubicacion_id','estado_base','estado_label','fecha_alta','fecha_baja','fecha_vto','user_id'
    ];

    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'fecha_alta' => 'date',
        'fecha_baja' => 'date',
        'fecha_vto'  => 'date',
    ];
}
