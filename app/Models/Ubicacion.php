<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\ComercioEstado;

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

    public function estadoModel()
    {
        return $this->belongsTo(ComercioEstado::class, 'estado', 'codigo');
    }

    protected static function booted()
    {
        static::saving(function (Ubicacion $m) {
            $nuevo = $m->estado ?: 'entramite';
            $previo = $m->getOriginal('estado') ?: null;
            $hoy = Carbon::today();

            // CREATE vs UPDATE
            $esCreate = ! $m->exists;

            // ---- ENT RAMITE ----
            if ($nuevo === 'entramite') {
                $m->fecha_alta = null;
                $m->fecha_baja = null;
                $m->fecha_vto  = null;
            }

            // ---- VIGENTE ----
            if ($nuevo === 'vigente') {
                if ($esCreate) {
                    // Carga inicial de un comercio ya vigente -> exigir fecha_alta desde el form
                    // (si viene vacía, no la inventamos)
                    if ($m->fecha_alta) {
                        $m->fecha_vto = Carbon::parse($m->fecha_alta)->addYearNoOverflow();
                    }
                } else {
                    // Transición a vigente
                    if ($previo === 'entramite' && empty($m->fecha_alta)) {
                        // Caso especial: venía en trámite -> alta HOY
                        $m->fecha_alta = $hoy;
                    }
                    if ($m->fecha_alta) {
                        $m->fecha_vto = Carbon::parse($m->fecha_alta)->addYearNoOverflow();
                    }
                }
                $m->fecha_baja = null;
            }

            // ---- IRREGULAR ----
            if ($nuevo === 'irregular') {
                // Siempre requiere fecha_alta; si no vino y no tenía, no inventamos (lo exige la UI/validación)
                if ($m->fecha_alta) {
                    $m->fecha_vto = Carbon::parse($m->fecha_alta)->addYearNoOverflow();
                } else {
                    $m->fecha_vto = null;
                }
                $m->fecha_baja = null;
            }

            // ---- BAJA ----
            if ($nuevo === 'baja') {
                if (empty($m->fecha_alta)) {
                    // si no tenía alta, la pedimos en la UI; como fallback, setear hoy para no dejar inconsistente:
                    $m->fecha_alta = $m->fecha_alta ?: $hoy;
                }
                $m->fecha_baja = $hoy;
                $m->fecha_vto  = null;
            }
        });
    }

    // Para la UI
    public function getHabilitaSeguimientoAttribute(): bool
    {
        return (bool) optional($this->estadoModel)->habilita_seguimiento;
    }




    protected $fillable = [
        'persona_tipo',
        'apellido',
        'nombres',
        'razon_social',
        'dni_cuit',
        'rubro_id',
        'domicilio_responsable',
        'correo',
        'telefono',
        'nombre_comercial',
        'domicilio_comercio',
        'nomenclatura',
        'observaciones',
        'estado',           // vigente | irregular | entramite
        'situacion',        // alta | baja
        'fecha_alta',
        'fecha_baja',
    ];

    // Si deseas deshabilitar los timestamps en el modelo
    public $timestamps = false;
}
