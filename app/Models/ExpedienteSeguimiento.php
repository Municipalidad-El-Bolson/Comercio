<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpedienteSeguimiento extends Model
{
    protected $fillable = ['ubicacion_id', 'sector_desde', 'sector_hasta', 'fecha', 'observacion', 'user_id'];
    protected $casts = ['fecha' => 'date'];

    public function user() { return $this->belongsTo(User::class); }
    public function ubicacion() { return $this->belongsTo(Ubicacion::class); }
}
