<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UbicacionTelefono extends Model
{
    protected $table = 'ubicacion_telefonos';
    protected $fillable = ['ubicacion_id','telefono','tipo'];
}