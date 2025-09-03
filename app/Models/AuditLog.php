<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class AuditLog extends Model
{
    protected $fillable = ['user_id','action','entity_type','entity_id','ip','method','path','meta'];
    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];
    protected $appends = ['message','subtitle','diff_lines','chips'];

    public function entity(): MorphTo
    {
        // Usa entity_type + entity_id
        return $this->morphTo(__FUNCTION__, 'entity_type', 'entity_id');
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    
    public function chips(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(fn () => [
            $this->created_at?->format('d/m/Y H:i'),
        ]);
    }

    public function message(): Attribute
    {
        return Attribute::get(function () {
            // 0) Mapear por nombre de ruta o action
            $routesMap = config('audit.routes', []);
            $routeName = Arr::get($this->meta, 'route');

            if ($routeName && isset($routesMap[$routeName])) {
                return $routesMap[$routeName];
            }

            $rawAction = (string)($this->attributes['action'] ?? '');
            if ($rawAction && isset($routesMap[$rawAction])) {
                return $routesMap[$rawAction];
            }

            // Fallback por path
            $p = (string)($this->path ?? '');
            if ($p !== '') {
                if (Str::contains($p, 'login'))  return 'Inicio de sesión';
                if (Str::contains($p, 'logout')) return 'Cierre de sesión';
            }

            // Verbo según meta['action']
            $actionKey = Arr::get($this->meta, 'action');
            $verb = match ($actionKey) {
                'created' => 'Se creó',
                'updated' => 'Se modificó',
                'deleted' => 'Se eliminó',
                default   => 'Se realizó',
            };

            // Tipo de entidad (seguro)
            $etype = Arr::get($this->meta, 'entity_type');
            $ename = class_basename($etype ?: (is_object($this->entity) ? get_class($this->entity) : ''));

            // ===== Ubicacion =====
            if (Str::lower($ename) === 'ubicacion') {
                $u = $this->entity; // morphTo

                // Tomamos datos directamente de Ubicacion (sin relación comercio)
                $fantasia = $u?->nombre_comercial
                    ?? $u?->razon_social
                    ?? trim(($u->apellido ?? '').' '.($u->nombres ?? ''));

                if ($fantasia) {
                    return "{$verb} {$fantasia}";
                }
                return "{$verb} la Ubicación #{$this->entity_id}";
            }

            // ===== Movimiento =====
            if (Str::lower($ename) === 'movimiento') {
                $m = $this->entity;
                $tipo   = trim((string) ($m?->tipo ?? ''));
                $titulo = trim((string) ($m?->titulo ?? ''));
                $texto  = trim($tipo.' '.$titulo);
                return $texto !== '' ? "{$verb} «{$texto}»" : "{$verb} el Movimiento #{$this->entity_id}";
            }

            // Fallback
            if (!empty($this->attributes['action'])) {
                return $this->attributes['action'];
            }
            $art = in_array(Str::lower($ename), ['ubicacion','inspeccion','tarea']) ? 'la' : 'el';
            $cls = $ename ?: 'Movimiento';
            return "{$verb} {$art} {$cls} #{$this->entity_id}";
        });
    }

    public function subtitle(): Attribute
    {
        // solo el usuario, sin método/path/IP
        return Attribute::get(fn () => $this->user?->name ?? 'Invitado');
    }


    public function diffLines(): Attribute
    {
        return Attribute::get(function () {
            $diff = Arr::get($this->meta, 'diff', []);
            if (empty($diff) || !is_array($diff)) return [];

            $fieldMap = config('audit.fields', []);

            return collect($diff)->map(function ($change, $field) use ($fieldMap) {
                $label = $fieldMap[$this->entity_type][$field]
                    ?? $fieldMap['*'][$field]
                    ?? ucfirst(str_replace('_',' ',$field));

                $old = $this->beautify($field, $change['old'] ?? null);
                $new = $this->beautify($field, $change['new'] ?? null);

                return "{$label}: {$old} → {$new}";
            })->values()->all();
        });
    }

    // ---- helpers ----
    protected function entityLabelAndArticle(): array
    {
        $cfg = config('audit.entities', []);
        $info = $cfg[$this->entity_type] ?? null;
        $label = $info['label'] ?? class_basename($this->entity_type);
        $gender = $info['gender'] ?? 'm';
        $article = ($gender === 'f') ? 'la' : 'el';
        return [$label, $article];
    }

    protected function beautify(string $field, $value): string
    {
        if ($value === null || $value === '') return '(vacío)';
        if (is_bool($value)) return $value ? 'Sí' : 'No';
        if (is_array($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);

        if (str_ends_with($field, '_at')) {
            try { return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i'); }
            catch (\Throwable) {}
        }
        return (string) $value;
    }
}
