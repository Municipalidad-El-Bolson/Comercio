<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MesaEntradaRegistro extends Model
{
    protected $table = 'mesa_entrada_registros';

    protected $fillable = [
        'fecha',
        'nro_ingreso',
        'titular_razon',
        'hc',
        'documentos',
        'observacion',
        'user_id',
        'sender_name',
    ];

    protected $casts = [
        'fecha' => 'date',
        'documentos' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
