<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AuditsModelChanges;

class Movimiento extends Model
{
    use AuditsModelChanges;

    public const ACTA_TIPOS = ['asesoramiento','notificacion','inspeccion','infraccion'];
    
    protected $fillable = [
        'ubicacion_id',
        'tipo',
        'tipo_acta',
        'titulo',
        'descripcion',
        'estado',
        'archivo',
        'etapa',
        'fecha',
        'observacion',
    ];

    protected $casts = [
        'fecha'       => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public $timestamps = true;
    
    public function auditMessage(string $action, array $meta): string
    {
        // armo el texto base
        $texto = trim("{$this->tipo_acta} {$this->titulo}");

        // elegir artículo según tipo_acta
        $articulo = $this->articuloPorTipo($this->tipo_acta);

        return match ($action) {
            'created' => "Se creó {$articulo} {$texto}",
            'updated' => "Se modificó {$articulo} {$texto}",
            'deleted' => "Se eliminó {$articulo} {$texto}",
            default   => "{$texto} {$action}",
        };
    }

    /**
     * Devuelve "el" o "la" según el tipo_acta.
     */
    protected function articuloPorTipo(?string $tipo): string
    {
        $tipo = strtolower(trim((string)$tipo));

        // reglas simples
        return match (true) {
            str_starts_with($tipo, 'notificacion') => 'la',
            str_starts_with($tipo, 'inspeccion') => 'la',
            str_starts_with($tipo, 'infraccion') => 'la',
            str_starts_with($tipo, 'acta')         => 'el',
            str_starts_with($tipo, 'asesoramiento')=> 'el',
            // por defecto "el"
            default => 'el',
        };
    }

    public function ubicacion()
    {
        return $this->belongsTo(\App\Models\Ubicacion::class);
    }
}
