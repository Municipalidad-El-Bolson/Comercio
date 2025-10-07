<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Ubicacion;
use App\Notifications\ProxVtoNotification;

class AlertProximosVto extends Command
{
    protected $signature = 'vto:alert-proximos';
    protected $description = 'Notifica comercios que vencen este mes; alerta a 10 días de vencer';

    public function handle(): int
    {
        $hoy = Carbon::today();
        $ini = $hoy->copy()->startOfMonth();
        $fin = $hoy->copy()->endOfMonth();

        $items = Ubicacion::query()
            ->whereNotNull('fecha_vto')
            ->whereBetween('fecha_vto', [$ini->toDateString(), $fin->toDateString()])
            ->orderBy('fecha_vto')
            ->get();

        $destinatarios = User::whereIn('role', ['admin', 'writer', 'reader'])->get();

        foreach ($items as $u) {
            $vto = Carbon::parse($u->fecha_vto);
            $dias_restantes = $hoy->diffInDays($vto, false);

            if ($dias_restantes === 10) {
                $nombre = $u->nombre_comercial
                    ?: ($u->razon_social ?: trim(($u->apellido ?? '').' '.($u->nombres ?? '')));

                foreach ($destinatarios as $usr) {
                    $ya = $usr->notifications()
                        ->where('type', ProxVtoNotification::class)
                        ->whereJsonContains('data->ubicacion_id', $u->id)
                        ->whereDate('created_at', $hoy->toDateString())
                        ->exists();

                    if (!$ya) {
                        $usr->notify(new ProxVtoNotification(
                            ubicacion_id: $u->id,
                            nombre: $nombre ?: "Ubicación #{$u->id}",
                            fecha_vto: (string) $u->fecha_vto,
                            dias_restantes: 10
                        ));
                    }
                }
            }
        }

        $this->info('Alertas de próximos vencimientos verificadas.');
        return self::SUCCESS;
    }
}
