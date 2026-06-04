<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Models\Ubicacion;
use App\Models\User;
use App\Notifications\VencidoNotification;

class VtoRebuild extends Command
{
    protected $signature = 'vto:rebuild {--dry : No guarda cambios ni notifica}';
    protected $description = 'Normaliza estado/estado_base y marca como vencidos los que ya están vencidos, notificando.';

    public function handle(): int
    {
        $dry = (bool)$this->option('dry');
        $hoy = Carbon::today();

        $destinatarios = User::whereIn('role', ['admin','writer','reader'])->get();

        Ubicacion::query()
            ->whereNotNull('fecha_vto')
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use ($hoy, $destinatarios, $dry) {
                foreach ($chunk as $u) {
                    $base = $this->normalizarBase($u->estado_base ?: $u->estado);

                    // Si vto en el pasado y base actual es 021 → pasar a 032 (vencido)
                    if ($u->fecha_vto && $u->fecha_vto->lt($hoy) && $base === '021') {
                        if (!$dry) {
                            $anterior = $u->estado_base ?: '021';
                            // Cambiar base + canon y guardar sin disparar timestamps
                            $u->estado_base = '032';
                            $u->estado = 'irregular'; // canónico acorde a tu mapeo
                            $u->saveQuietly();

                            // Notificación (evitar duplicados del día)
                            foreach ($destinatarios as $usr) {
                                $ya = $usr->notifications()
                                    ->where('type', VencidoNotification::class)
                                    ->whereJsonContains('data->ubicacion_id', $u->id)
                                    ->whereDate('created_at', now()->toDateString())
                                    ->exists();
                                if (!$ya) {
                                    $nombre = $u->nombre_comercial
                                        ?: ($u->razon_social ?: trim(($u->apellido ?? '').' '.($u->nombres ?? '')));
                                    $usr->notify(new VencidoNotification(
                                        ubicacion_id: $u->id,
                                        nombre: $nombre ?: "Ubicación #{$u->id}",
                                        fecha_vto: optional($u->fecha_vto)->format('Y-m-d'),
                                        fecha_cambio: now()->format('Y-m-d H:i'),
                                        estado_anterior: $anterior,
                                        estado_nuevo: '032'
                                    ));
                                }
                            }
                        }

                        $this->line("✔ {$u->id} → marcado 032 (vencido)".($dry?' [dry]':''));
                    }
                }
            });

        $this->info('Rebuild completado.');
        return self::SUCCESS;
    }

    private function normalizarBase(?string $raw): string
    {
        $s = trim(mb_strtolower((string)$raw));
        if ($s === '') return '021';

        if (str_starts_with($s, '021')) return '021';
        if (str_starts_with($s, '032')) return '032';
        if (str_starts_with($s, '040')) return '040';

        return match ($s) {
            'entramite','en tramite','en trámite','alta','vigente' => '021',
            'irregular'                                           => '032',
            '040'                                                 => '040',
            'baja'                                                => 'baja',
            'baja de oficio','baja_oficio','baja-oficio'          => 'baja_oficio',
            'expediente sin efecto','sin_efecto','exp_sin_efecto' => 'exp_sin_efecto',
            default                                               => '021',
        };
    }
}
