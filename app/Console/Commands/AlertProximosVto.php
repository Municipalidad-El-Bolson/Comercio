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
    protected $description = 'Notifica comercios que vencen este mes; alerta a 10 días';

    public function handle(): int
    {
        $hoy   = Carbon::today();
        $ini   = $hoy->copy()->startOfMonth();
        $fin   = $hoy->copy()->endOfMonth();

        $items = Ubicacion::query()
            ->whereNotNull('fecha_vto')
            ->whereBetween('fecha_vto', [$ini->toDateString(), $fin->toDateString()])
            ->orderBy('fecha_vto') // urgentes primero
            ->get();

        // destinatarios (ajustá si querés otro subconjunto)
        $destinatarios = User::whereIn('role', ['admin','writer','reader'])->get();

        foreach ($items as $u) {
            $dias = Carbon::parse($u->fecha_vto)->diffInDays($hoy, false); // negativo si futuro
            $dias_restantes = Carbon::parse($u->fecha_vto)->diffInDays($hoy); // valor positivo

            // Avisar EXACTAMENTE cuando falten 10 días
            if ($dias < 0 && $dias_restantes === 10) {
                $nombre = $u->nombre_comercial ?: ($u->razon_social ?: trim(($u->apellido ?? '').' '.($u->nombres ?? '')));
                foreach ($destinatarios as $usr) {
                    // evita duplicados del día
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