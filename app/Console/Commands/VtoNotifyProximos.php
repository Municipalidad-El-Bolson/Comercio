<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Ubicacion;
use App\Notifications\ProxVtoNotification;

class VtoNotifyProximos extends Command
{
    protected $signature = 'vto:notify-proximos {--days=10}';
    protected $description = 'Notifica comercios que vencen en los próximos X días (default 10) dentro del mes.';

    public function handle(): int
    {
        $days = (int)$this->option('days');
        $hoy  = Carbon::today();
        $limite = $hoy->copy()->addDays($days);
        $finMes = $hoy->copy()->endOfMonth();

        // Vence > hoy y <= min(fin de mes, hoy+days) y solo estados "activos"
        $items = Ubicacion::query()
            ->whereIn('estado_base', ['021','040'])
            ->whereNotNull('fecha_vto')
            ->whereDate('fecha_vto', '>', $hoy->toDateString())
            ->whereDate('fecha_vto', '<=', min($limite, $finMes)->toDateString())
            ->get();

        $destinatarios = User::whereIn('role', ['admin','writer','reader'])->get();

        foreach ($items as $u) {
            $dias = $hoy->diffInDays($u->fecha_vto, false);
            $nombre = $u->nombre_comercial
                ?: ($u->razon_social ?: trim(($u->apellido ?? '').' '.($u->nombres ?? '')));

            foreach ($destinatarios as $usr) {
                $ya = $usr->notifications()
                    ->where('type', ProxVtoNotification::class)
                    ->whereJsonContains('data->ubicacion_id', $u->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if (!$ya) {
                    $usr->notify(new ProxVtoNotification(
                        ubicacion_id: $u->id,
                        nombre: $nombre ?: "Ubicación #{$u->id}",
                        fecha_vto: optional($u->fecha_vto)->format('Y-m-d'),
                        dias_restantes: max(0, $dias)
                    ));
                }
            }
        }

        $this->info('Notificaciones de próximos generadas.');
        return self::SUCCESS;
    }
}
