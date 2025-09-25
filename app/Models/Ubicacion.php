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
    ];

    // ─── Scopes ─────────────────────────────────────────────────────────────────
    public function scopeVigentes($q)  { return $q->where('estado','vigente')->whereDate('fecha_vto','>=', now()); }
    public function scopeVencidos($q)  { return $q->where('estado','vigente')->whereDate('fecha_vto','<',  now()); }
    public function scopeEnTramite($q) { return $q->where('estado','entramite'); }
    public function scopeClausurados($q){ return $q->where('situacion','clausurado'); } // ← corregido

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

    // ─── Hooks de modelo ────────────────────────────────────────────────────────
    protected static function booted()
    {
        static::saving(function (Ubicacion $m) {
            // Normalizar dirección (sin forzar ciudad/país duplicado)
            $m->domicilio_comercio = self::normalizeDireccionComercio($m->domicilio_comercio);

            $estado = $m->estado ?: 'entramite';

            // Si ya está marcado como clausurado, respetar
            if ($m->situacion === 'clausurado') {
                // fechas no se tocan; el flag de clausura manda
                return;
            }

            // Mapear situacion por estado (sin pisar clausurado)
            $m->situacion = match ($estado) {
                'vigente', 'irregular' => 'alta',
                'baja', 'baja_oficio', 'sin_efecto' => 'baja',
                default => null, // entramite u otros
            };

            // Fechas por estado:
            switch ($estado) {
                case 'entramite':
                    // En trámite: normalmente sin fechas (dejar manual si ya venían? tu lógica actual las limpia)
                    $m->fecha_alta = null;
                    $m->fecha_baja = null;
                    $m->fecha_vto  = null;
                    break;

                case 'vigente':
                case 'irregular':
                    // Alta: NO tocar fecha_vto (es manual). Solo asegurar baja = null.
                    $m->fecha_baja = null;
                    // fecha_alta la maneja el form/validador; no la forzamos.
                    break;

                case 'baja':
                case 'baja_oficio':
                case 'sin_efecto':
                    // Estados de baja: no tiene sentido mantener vencimiento
                    $m->fecha_vto = null;
                    // fecha_baja la controla el form (y validación); no la forzamos aquí
                    break;
            }
        });
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
