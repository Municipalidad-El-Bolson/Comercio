<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\ComercioEstado;
use Illuminate\Support\Str;
use App\Traits\AuditsModelChanges;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Ubicacion extends Model
{
    use AuditsModelChanges, HasFactory;

    protected $table = 'ubicaciones';

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
        'estado_base',
        'estado_label',
        'estado',        // entramite | vigente | irregular | baja | baja_oficio | sin_efecto
        'situacion',     // null | alta | baja | clausurado
        'tipo_hab',      // definitiva | prev
        'fecha_alta',
        'fecha_baja',
        'fecha_vto',
        'monto_pagar',
    ];

    protected $casts = [
        'fecha_alta'  => 'date',
        'fecha_baja'  => 'date',
        'fecha_vto'   => 'date',
        'lat'         => 'float',
        'lng'         => 'float',
        'monto_pagar' => 'decimal:2',
        'estado'       => 'string',
        'estado_base'  => 'string',
        'estado_label' => 'string',
    ];

    // ─── Scopes ─────────────────────────────────────────────────────────────────
    public function scopeVigentes($q)  { return $q->where('estado','vigente')->whereDate('fecha_vto','>=', now()); }
    public function scopeVencidos($q)  { return $q->where('estado','vigente')->whereDate('fecha_vto','<',  now()); }
    public function scopeEnTramite($q) { return $q->where('estado','entramite'); }
    public function scopeClausurados($q){ return $q->where('situacion','clausurado'); }
    public function scopeCodigo021($q){ return $q->where('estado_base','021'); }
    public function scopeCodigo032($q){ return $q->where('estado_base','032'); }
    public function scopeBaja($q){ return $q->where('estado_base','baja'); }
    public function scopeBajaOficio($q){ return $q->where('estado_base','baja_oficio'); }
    public function scopeExpSinEfecto($q){ return $q->where('estado_base','exp_sin_efecto'); }


    // ─── Rels ───────────────────────────────────────────────────────────────────
    public function rubro(): BelongsTo { return $this->belongsTo(Rubro::class, 'rubro_id'); }

    public function rubros(): BelongsToMany
    {
        return $this->belongsToMany(Rubro::class, 'ubicacion_rubro')
            ->withTimestamps()
            ->withPivot('orden')
            ->orderBy('ubicacion_rubro.orden');
    }

    public function telefonos(): HasMany       { return $this->hasMany(UbicacionTelefono::class); }
    public function disposiciones(): HasMany    { return $this->hasMany(UbicacionDisposicion::class); }
    public function habilitaciones(): HasMany   { return $this->hasMany(UbicacionHabilitacion::class); }
    public function documentos()                { return $this->hasOne(UbicacionDocumento::class); }
    public function movimientos()               { return $this->hasMany(Movimiento::class); }
    public function estadoModel()               { return $this->belongsTo(ComercioEstado::class, 'estado', 'codigo'); }

    public function habilitacionActual(): HasOne
    {
        return $this->hasOne(\App\Models\UbicacionHabilitacion::class)
            ->orderByDesc('fecha')
            ->orderByDesc('id'); // fallback si fecha es nula o igual
    }

    // ─── Hooks de modelo ────────────────────────────────────────────────────────
    protected static function booted()
    {
        static::saving(function (Ubicacion $m) {
            // Normalizar dirección
            $m->domicilio_comercio = self::normalizeDireccionComercio($m->domicilio_comercio);

            $resolverBase = function (?string $raw): string {
                $e = trim(mb_strtolower((string)$raw));
                return match ($e) {
                    'entramite', 'en tramite', 'en trámite', '021' => '021',
                    'irregular', '032'                              => '032',
                    '040'                                           => '040',   // <-- NUEVO
                    'baja'                                          => 'baja',
                    'baja de oficio', 'baja_oficio', 'baja-oficio'  => 'baja_oficio',
                    'expediente sin efecto', 'exp_sin_efecto', 'exp-sin-efecto', 'sin_efecto'
                                                                    => 'exp_sin_efecto',
                    default                                         => ($raw ? $raw : '021'), // <-- NO fuerces a 021 a ciegas
                };
            };

            $estadoBase = $m->estado_base ?: $resolverBase($m->estado);
            $m->estado_base = $estadoBase;

            
            if ($m->situacion === 'clausurado') {
                return;
            }

            $m->estado = match ($estadoBase) {
                '021'            => 'entramite',
                '032'            => 'irregular',
                '040'            => '040',         
                'baja'           => 'baja',
                'baja_oficio'    => 'baja_oficio',
                'exp_sin_efecto' => 'sin_efecto',
                default          => $m->estado ?: 'entramite',
            };
            
            $m->situacion = in_array($estadoBase, ['baja','baja_oficio','exp_sin_efecto'], true)
                ? 'baja'
                : (in_array($estadoBase, ['021','032','040'], true) ? 'alta' : null); // <-- incluye 040

            switch ($estadoBase) {
                case '021':
                    $m->fecha_baja = null;
                    if (!empty($m->fecha_alta) && empty($m->fecha_vto)) {
                        $alta = $m->fecha_alta instanceof Carbon ? $m->fecha_alta->copy() : Carbon::parse($m->fecha_alta);
                        $m->fecha_vto = ($m->tipo_hab === 'definitiva') ? $alta->addYearNoOverflow()
                                                                        : $alta->addMonthsNoOverflow(6);
                    }
                    break;

                case '032':
                case '040': // <-- NUEVO: igual tratamiento que 032
                    $m->fecha_baja = null;
                    if (!empty($m->fecha_alta) && empty($m->fecha_vto)) {
                        $alta = $m->fecha_alta instanceof Carbon ? $m->fecha_alta->copy() : Carbon::parse($m->fecha_alta);
                        $m->fecha_vto = ($m->tipo_hab === 'definitiva') ? $alta->addYearNoOverflow()
                                                                        : $alta->addMonthsNoOverflow(6);
                    }
                    break;

                case 'baja':
                case 'baja_oficio':
                case 'exp_sin_efecto':
                    $m->fecha_vto = null; // en bajas no aplica vto
                    break;
            }
        });
    }

    public function setEstadoAttribute($value): void
    {
        $e = trim(mb_strtolower((string)$value));

        // si ya viene canónico, lo dejamos (agregamos 040)
        if (in_array($e, ['entramite','vigente','irregular','baja','baja_oficio','sin_efecto','040'], true)) {
            $this->attributes['estado'] = $e;
            return;
        }

        // mapear variantes a base
        $base = match ($e) {
            '021','en tramite','en trámite','en_tramite','en-tramite','alta','vigente' => '021',
            '032','irregular'                                                          => '032',
            '040'                                                                       => '040',
            'baja'                                                                      => 'baja',
            'baja de oficio','baja_oficio','baja-de-oficio'                             => 'baja_oficio',
            'expediente sin efecto','sin_efecto','exp_sin_efecto'                       => 'exp_sin_efecto',
            default                                                                     => '021',
        };

        $canon = match ($base) {
            '021'            => 'entramite',
            '032'            => 'irregular',
            '040'            => '040',        // <-- NUEVO
            'baja'           => 'baja',
            'baja_oficio'    => 'baja_oficio',
            'exp_sin_efecto' => 'sin_efecto',
            default          => 'entramite',
        };

        $this->attributes['estado'] = $canon;
    }


    // ─── Atributos auxiliares para la UI ───────────────────────────────────────
    public function getHabilitaSeguimientoAttribute(): bool
    { return (bool) optional($this->estadoModel)->habilita_seguimiento; }

    public function getTipoHabLabelAttribute(): string
    {
        return match($this->tipo_hab) {
            'definitiva' => 'Definitiva',
            'prev'       => 'Provisoria',
            default      => 'Provisoria',
        };
    }

    // App\Models\Ubicacion.php
    public function estadosHistorial()
    { return $this->hasMany(\App\Models\UbicacionEstadoHist::class,'ubicacion_id')->latest('created_at'); }


    public function getEsClausuradoAttribute(): bool
    { return $this->situacion === 'clausurado'; }

    // ─── Mutators ──────────────────────────────────────────────────────────────
    public function setFechaVtoAttribute($value)
    { $this->attributes['fecha_vto'] = $value ? Carbon::parse($value) : null; }

    public function setFechaAltaAttribute($value)
    { $this->attributes['fecha_alta'] = $value ? Carbon::parse($value) : null; }

    public function setFechaBajaAttribute($value)
    { $this->attributes['fecha_baja'] = $value ? Carbon::parse($value) : null; }

    // ─── Normalizador de dirección ────────────────────────────────────────────
    private static function normalizeDireccionComercio(?string $dir): ?string
    {
        if ($dir === null) return null;
        $dir = trim($dir);
        if ($dir === '') return null;

        $dir = preg_replace('/\s+/', ' ', $dir);
        $dir = rtrim($dir, " \t\n\r\0\x0B,");

        // Sufijo único
        $suffix = ', R8430 El Bolsón, Río Negro, Argentina';

        // Variantes a limpiar (con/sin acentos)
        $lower = mb_strtolower($dir);
        $variants = [
            ', r8430 el bolsón, río negro, argentina',
            ', r8430 el bolson, rio negro, argentina',
            ', r8430 el bolsón, rio negro, argentina',
            ', r8430 el bolson, río negro, argentina',
            ', r8430 el bolsón, río negro',
            ', r8430 el bolson, rio negro',
            ', r8430 el bolsón, rio negro',
            ', r8430 el bolson, río negro',
        ];

        foreach ($variants as $v) {
            if (Str::endsWith($lower, $v)) {
                $dir = mb_substr($dir, 0, mb_strlen($dir) - mb_strlen($v));
                $dir = rtrim($dir, " \t\n\r\0\x0B,");
                break;
            }
        }

        return $dir . $suffix;
    }

    // Si tu tabla no tiene timestamps:
    public $timestamps = false;

    public function getEstadoDisplayAttribute(): string
    {
        if (!empty($this->estado_label)) return $this->estado_label;

        if (!empty($this->estado_base)) {
            return match ($this->estado_base) {
                '021'            => '021',
                '032'            => '032',
                '040'            => '040',
                'baja'           => 'Baja',
                'baja_oficio'    => 'Baja de Oficio',
                'exp_sin_efecto' => 'Expediente sin Efecto',
                default          => strtoupper((string)$this->estado_base),
            };
        }

        return match ($this->estado) {
            'entramite'   => '021',
            'irregular'   => '032',
            '040'         => '040', 
            'baja'        => 'Baja',
            'baja_oficio' => 'Baja de Oficio',
            'sin_efecto'  => 'Expediente sin Efecto',
            default       => strtoupper((string)$this->estado),
        };
    }

    public function auditMessage(string $action, array $meta = []): string
    {
        $nombre = $this->nombre_comercial;
        return match ($action) {
            'created' => "Se creó el comercio {$nombre}",
            'updated' => "Se modificó el comercio {$nombre}",
            'deleted' => "Se eliminó el comercio {$nombre}",
            default   => "Comercio {$nombre}: {$action}",
        };
    }
}
