<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Ubicacion;
use App\Notifications\VencidoNotification;

class MarkVencidos extends Command
{
    protected $signature = 'vto:mark-vencidos';
    protected $description = 'Marca ubicaciones vencidas (021→032) y notifica el cambio';

    public function handle(): int
    {
        $hoy = Carbon::today()->toDateString();

        // Tomamos las que vencieron hoy o antes, con estado 021
        $items = Ubicacion::query()
            ->where('estado', '021')
            ->whereDate('fecha_vto', '<', $hoy)
            ->get();

        $destinatarios = \App\Models\User::whereIn('role', ['admin','writer','reader'])->get();

        foreach ($items as $u) {
            $anterior = $u->estado;
            $u->estado = '032'; // vencido
            $u->save(); // si querés auditar, tu trait lo hace

            $nombre = $u->nombre_comercial ?: ($u->razon_social ?: trim(($u->apellido ?? '').' '.($u->nombres ?? '')));
            foreach ($destinatarios as $usr) {
                // evita duplicados en el mismo minuto (por si corre dos veces)
                $ya = $usr->notifications()
                    ->where('type', VencidoNotification::class)
                    ->whereJsonContains('data->ubicacion_id', $u->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();
                if (!$ya) {
                    $usr->notify(new VencidoNotification(
                        ubicacion_id: $u->id,
                        nombre: $nombre ?: "Ubicación #{$u->id}",
                        fecha_vto: (string) $u->fecha_vto,
                        fecha_cambio: now()->format('Y-m-d H:i'),
                        estado_anterior: $anterior,
                        estado_nuevo: '032'
                    ));
                }
            }
        }

        $this->info('Vencidos marcados y notificados.');
        return self::SUCCESS;
    }
}