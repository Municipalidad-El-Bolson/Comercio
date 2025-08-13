<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    protected $table = 'ubicaciones';

    use HasFactory;

    public function rubro()
    {
        return $this->belongsTo(Rubro::class);
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class);
    }


    protected $fillable = [
        'razon_social',
        'apellido',
        'nombres',
        'dni',
        'rubro_id',
        'direccion',
        'latitud',
        'longitud',
        'habilitado',
        'estado',
        'tipo'
    ];

    // Si deseas deshabilitar los timestamps en el modelo
    public $timestamps = false;
}
