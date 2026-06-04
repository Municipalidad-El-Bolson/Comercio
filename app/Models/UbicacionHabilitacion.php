<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UbicacionHabilitacion extends Model
{
    protected $table = 'ubicacion_habilitaciones';
    protected $fillable = ['ubicacion_id','numero','fecha'];
}