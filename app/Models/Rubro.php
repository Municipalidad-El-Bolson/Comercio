<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    public function esAlojamientoTuristico(): bool
    {
        $categoria = Str::of(implode(' ', [
            (string) ($this->rubro_general ?? ''),
            (string) ($this->rubro_madre ?? ''),
        ]))->ascii()->lower()->squish()->toString();

        return str_contains($categoria, 'alojamiento')
            && (str_contains($categoria, 'turistico') || str_contains($categoria, 'alquiler'));
    }

    public function esCamping(): bool
    {
        return Str::contains(
            Str::of((string) $this->subrubro)->ascii()->lower()->squish()->toString(),
            'camping'
        );
    }
}
