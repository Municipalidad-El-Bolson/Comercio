<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rubro extends Model
{

    protected $fillable = [
        'id',
        'rubro_madre',
        'subrubro'
    ];

    public function ubicaciones()
    {
        return $this->hasMany(Ubicacion::class);
    }
    // Si deseas deshabilitar los timestamps en el modelo
    public $timestamps = false;
}
