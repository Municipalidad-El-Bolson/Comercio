<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\ComercioEstado;
use Illuminate\Support\Str;
use App\Traits\AuditsModelChanges;

class Ubicacion extends Model
{
    use AuditsModelChanges, HasFactory;
    
    protected $table = 'ubicaciones';

    use HasFactory;

    public function scopeVigentes($q) { return $q->where('estado','vigente')->whereDate('fecha_vto','>=',now()); }
    public function scopeVencidos($q) { return $q->where('estado','vigente')->whereDate('fecha_vto','<',now()); }
    public function scopeEnTramite($q){ return $q->where('estado','entramite'); }
    public function scopeClausurados($q){ return $q->where('estado','irregular'); }


    public function rubro(){ return $this->belongsTo(Rubro::class, 'rubro_id'); }

    public function documentos(){ return $this->hasOne(UbicacionDocumento::class);}

    public function movimientos(){ return $this->hasMany(Movimiento::class);}

    public function estadoModel(){ return $this->belongsTo(ComercioEstado::class, 'estado', 'codigo');}

    protected static function booted()
    {
        static::saving(function (Ubicacion $m) {
            $m->domicilio_comercio = self::normalizeDireccionComercio($m->domicilio_comercio);

            $estado  = $m->estado ?: 'entramite';
            $tipo    = $m->tipo_hab ?: 'definitiva';
            $alta    = $m->fecha_alta;

            if ($estado === 'entramite') {
                $m->situacion  = null;
                $m->fecha_alta = null;
                $m->fecha_baja = null;
                $m->fecha_vto  = null;
                return;
            }

            if ($estado === 'vigente' || $estado === 'irregular') {
                $m->situacion = 'alta';
                $m->fecha_baja = null;

                if ($m->fecha_alta) {
                    $alta = $m->fecha_alta instanceof \Carbon\Carbon ? $m->fecha_alta->copy() : \Carbon\Carbon::parse($m->fecha_alta);
                    $m->fecha_vto = $tipo === 'definitiva'
                        ? $alta->addYearNoOverflow()
                        : $alta->addMonthsNoOverflow(6);
                } else {
                    $m->fecha_vto = null;
                }
                return;
            }

            if ($estado === 'baja') {
                $m->situacion = 'baja';
                // No tocamos alta/baja: vienen del form. VTO no aplica
                $m->fecha_vto = null;
            }
        });
    }

    private static function calcularVto(Carbon|string $fechaAlta, string $tipo): Carbon
    {
        $alta = $fechaAlta instanceof Carbon ? $fechaAlta->copy() : Carbon::parse($fechaAlta);
        
        return match ($tipo) {
            'definitiva' => $alta->addYearNoOverflow(),
            'prev'       => $alta->addMonthsNoOverflow(6),
            default      => $alta->addMonthsNoOverflow(6),
        };
    }

    // Para la UI
    public function getHabilitaSeguimientoAttribute(): bool{ return (bool) optional($this->estadoModel)->habilita_seguimiento;}

    public function getTipoHabLabelAttribute(): string
    {
        return match($this->tipo_hab) {
            'definitiva' => 'Definitiva',
            'prev'       => 'Provisoria',
            default      => 'Provisoria',
        };
    }


    private static function normalizeDireccionComercio(?string $dir): ?string
    {
        if ($dir === null) return null;
        $dir = trim($dir);
        if ($dir === '') return null;

        // Compactar espacios
        $dir = preg_replace('/\s+/', ' ', $dir);

        // Remover comas/espacios finales sobrantes
        $dir = rtrim($dir, " \t\n\r\0\x0B,");

        // Sufijo correcto (único)
        $suffix = ', R8430 El Bolsón, Río Negro';

        // Quitar variantes del sufijo existentes (con/sin acentos)
        $lower = mb_strtolower($dir);

        $variants = [
            ', r8430 el bolsón, río negro',
            ', r8430 el bolson, rio negro',
            ', r8430 el bolsón, rio negro',
            ', r8430 el bolson, río negro',
        ];

        foreach ($variants as $v) {
            if (Str::endsWith($lower, $v)) {
                // cortar el sufijo existente
                $dir = mb_substr($dir, 0, mb_strlen($dir) - mb_strlen($v));
                $dir = rtrim($dir, " \t\n\r\0\x0B,");
                break;
            }
        }

        // Volver a pegar el sufijo correcto, una sola vez
        return $dir . $suffix;
    }

    public function auditMessage(string $action, array $meta = []): string
    {
        // ajustá el campo que tenga el “nombre” real de la ubicación:
        $nombre = $this->nombre ?? $this->razon_social ?? ('#'.$this->getKey());

        return match ($action) {
            'created' => "Se creó la ubicación {$nombre}",
            'updated' => "Se actualizó la ubicación {$nombre}",
            'deleted' => "Se eliminó la ubicación {$nombre}",
            default   => "Ubicación {$nombre}: {$action}",
        };
    }

    protected $casts = [
        'fecha_alta' => 'date',
        'fecha_baja' => 'date',
        'fecha_vto'  => 'date',
    ];

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
        'lat','lng','barrio','cpu_cod','cpu_nombre',
        'observaciones',
        'estado',           // vigente | irregular | entramite
        'situacion',
        'tipo_hab',        // alta | baja
        'fecha_alta',
        'fecha_baja',
        'fecha_vto',
    ];

    // Si deseas deshabilitar los timestamps en el modelo
    public $timestamps = false;
}
