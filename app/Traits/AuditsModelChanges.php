<?php

namespace App\Traits;

use App\Models\AuditLog;

trait AuditsModelChanges
{
    public static function bootAuditsModelChanges(): void
    {
        static::created(function ($model) {
            self::writeAudit('created', $model);
        });

        static::updated(function ($model) {
            $changes = [];
            foreach ($model->getChanges() as $field => $new) {
                if ($field === 'updated_at') continue;
                $changes[$field] = [
                    'old' => $model->getOriginal($field),
                    'new' => $new,
                ];
            }
            self::writeAudit('updated', $model, ['diff' => $changes]);
        });

        static::deleted(function ($model) {
            self::writeAudit('deleted', $model);
        });
    }

protected static function writeAudit(string $action, $model, array $meta = []): void
{
    if (!auth()->check()) {
        return;
    }
    $meta = [
        'action'      => $action,
        'route'       => optional(request()->route())->getName(),
        'is_livewire' => str_contains(request()?->path() ?? '', 'livewire/'),
    ] + $meta;

    $message = method_exists($model, 'auditMessage')
        ? $model->auditMessage($action, $meta)
        : self::defaultMessage($action, $model);

    \App\Models\AuditLog::create([
        'user_id'     => auth()->id(),
        'action'      => $message, 
        'entity_type' => get_class($model),
        'entity_id'   => (string) $model->getKey(),
        'ip'          => request()?->ip(),
        'method'      => request()?->method(),
        'path'        => request()?->path(),
        'meta'        => $meta,
    ]);
}

    protected static function defaultMessage(string $action, $model): string
    {
        $class = class_basename($model);
        return match ($action) {
            'created' => "Se creó un {$class} #{$model->getKey()}",
            'updated' => "Se actualizó un {$class} #{$model->getKey()}",
            'deleted' => "Se eliminó un {$class} #{$model->getKey()}",
            default   => "{$class} {$action}",
        };
    }
}
