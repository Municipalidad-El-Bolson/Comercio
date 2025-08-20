<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rubro extends Model
{
    protected $table = 'rubros';      
    protected $primaryKey = 'id';     

    protected $fillable = ['id','rubro_madre','subrubro'];

    public $timestamps = false;

    public $incrementing = true;
    protected $keyType = 'int';

    public function ubicaciones()
    {
        return $this->hasMany(Ubicacion::class);
    }
}
