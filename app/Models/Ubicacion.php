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
        return $this->belongsTo(Rubro::class, 'rubro_id');
    }
    public function documentos()
    { 
        return $this->hasOne(UbicacionDocumento::class); 
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class);
    }


    protected $fillable = [
        'persona_tipo',
        'apellido', 'nombres', 'razon_social',
        'dni_cuit',
        'rubro_id',
        'domicilio_responsable', 'correo', 'telefono',
        'nombre_comercial',
        'domicilio_comercio',
        'nomenclatura',
        'observaciones',
        'estado',           // vigente | irregular | entramite
        'situacion',        // alta | baja
        'fecha_alta','fecha_baja',
    ];

    // Si deseas deshabilitar los timestamps en el modelo
    public $timestamps = false;

}
