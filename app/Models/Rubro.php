<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rubro extends Model
{
    protected $table = 'rubros';      
    protected $primaryKey = 'id';     

    protected $fillable = ['id','mega_rubro','rubro_madre','subrubro'];

    public $timestamps = false;

    public $incrementing = true;
    protected $keyType = 'int';

    public function ubicaciones()
    {
        return $this->hasMany(Ubicacion::class, 'rubro_id');
    }

    public function ubicacionesPivot()
    {
        return $this->belongsToMany(Ubicacion::class, 'ubicacion_rubro')->withTimestamps();
    }
}
