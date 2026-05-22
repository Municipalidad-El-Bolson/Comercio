<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\ComercioEstado;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
            $m->domicilio_comercio = self::normalizeDireccionComercio($m->domicilio_comercio);

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
                $m->situacion = 'alta';
                if ($esCreate) {
                    // Carga inicial de un comercio ya vigente -> exigir fecha_alta desde el form
                    // (si viene vacía, no la inventamos)
                    if ($m->fecha_alta) {
                        $m->fecha_vto = self::calcularFechaVencimiento($m->fecha_alta, $m->tipo_habilitacion);
                    }
                } else {
                    // Transición a vigente
                    if ($previo === 'entramite' && empty($m->fecha_alta)) {
                        // Caso especial: venía en trámite -> alta HOY
                        $m->fecha_alta = $hoy;
                    }
                    if ($m->fecha_alta) {
                        $m->fecha_vto = self::calcularFechaVencimiento($m->fecha_alta, $m->tipo_habilitacion);
                    }
                }
                $m->fecha_baja = null;
            }

            // ---- IRREGULAR ----
            if ($nuevo === 'irregular') {
                $m->situacion = 'alta';
                // Siempre requiere fecha_alta; si no vino y no tenía, no inventamos (lo exige la UI/validación)
                if ($m->fecha_alta) {
                    $m->fecha_vto = self::calcularFechaVencimiento($m->fecha_alta, $m->tipo_habilitacion);
                } else {
                    $m->fecha_vto = null;
                }
                $m->fecha_baja = null;
            }

            // ---- BAJA ----
            if ($nuevo === 'baja') {
                $m->situacion = 'baja';
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

    public static function calcularFechaVencimiento($fechaAlta, ?string $tipoHabilitacion = null): Carbon
    {
        $fecha = Carbon::parse($fechaAlta);

        return $tipoHabilitacion === 'provisoria'
            ? $fecha->addMonthsNoOverflow(6)
            : $fecha->addYearNoOverflow();
    }


    protected $fillable = [
        'hc',
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
        'tipo_habilitacion', // provisoria | definitiva
        'situacion',        // alta | baja
        'fecha_alta',
        'fecha_baja',
        'fecha_vto',
    ];

    protected $casts = [
        'fecha_alta' => 'date',
        'fecha_baja' => 'date',
        'fecha_vto' => 'date',
    ];

    public function scopeProximasAVencer(Builder $query, int $dias = 30): Builder
    {
        return $query
            ->whereIn('estado', ['vigente', 'irregular'])
            ->where('situacion', 'alta')
            ->whereNotNull('fecha_vto')
            ->whereDate('fecha_vto', '>=', Carbon::today())
            ->whereDate('fecha_vto', '<=', Carbon::today()->addDays($dias));
    }

    public function getFechaVencimientoCalculadaAttribute(): ?Carbon
    {
        if (!in_array($this->estado, ['vigente', 'irregular'], true)) {
            return null;
        }

        if ($this->fecha_vto) {
            return $this->fecha_vto->copy();
        }

        return $this->fecha_alta
            ? self::calcularFechaVencimiento($this->fecha_alta, $this->tipo_habilitacion)
            : null;
    }

    public static function obtenerProximasAVencer(int $dias = 30, array $with = []): Collection
    {
        $desde = Carbon::today();
        $hasta = $desde->copy()->addDays($dias);

        return static::with($with)
            ->whereIn('estado', ['vigente', 'irregular'])
            ->where('situacion', 'alta')
            ->where(function (Builder $query) {
                $query->whereNotNull('fecha_vto')
                    ->orWhereNotNull('fecha_alta');
            })
            ->get()
            ->filter(function (Ubicacion $ubicacion) use ($desde, $hasta) {
                $vencimiento = $ubicacion->fecha_vencimiento_calculada;

                return $vencimiento
                    && $vencimiento->greaterThanOrEqualTo($desde)
                    && $vencimiento->lessThanOrEqualTo($hasta);
            })
            ->sortBy(fn (Ubicacion $ubicacion) => sprintf(
                '%s|%s',
                $ubicacion->fecha_vencimiento_calculada?->format('Y-m-d') ?? '',
                $ubicacion->razon_social ?? ''
            ))
            ->values();
    }

    // Si deseas deshabilitar los timestamps en el modelo
    public $timestamps = false;
}
