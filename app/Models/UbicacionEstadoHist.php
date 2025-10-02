<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UbicacionEstadoHist extends Model
{
  protected $table = 'ubicacion_estado_historial';
  protected $fillable = [
    'ubicacion_id','estado_base','estado_label','fecha_alta','fecha_baja','fecha_vto','user_id'
  ];
}
