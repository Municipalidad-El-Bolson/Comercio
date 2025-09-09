<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UbicacionDisposicion extends Model
{
    protected $table = 'ubicacion_disposiciones';
    protected $fillable = ['ubicacion_id','numero','fecha'];
}