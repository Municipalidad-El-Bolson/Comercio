<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rubro extends Model
{
    protected $table = 'rubros';      
    protected $primaryKey = 'id';     

    protected $fillable = [
        'rubro_madre',
        'subrubro',
    ];

    public $timestamps = false;

    public function ubicaciones()
    {
        return $this->hasMany(Ubicacion::class);
    }
}
