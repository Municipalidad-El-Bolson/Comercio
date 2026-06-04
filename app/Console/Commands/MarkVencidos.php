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
    protected $description = 'Marca ubicaciones vencidas (→ 032) y notifica el cambio';

    public function handle(): int
    {
        $hoy = Carbon::today()->toDateString();

        // ✅ Tomar TODOS los que vencieron y NO están ya 032 ni en bajas
        $items = Ubicacion::query()
            ->whereNotNull('fecha_vto')
            ->whereDate('fecha_vto', '<', $hoy)
            ->whereNotIn('estado_base', ['032', 'baja', 'baja_oficio', 'exp_sin_efecto'])
            ->get();

        $destinatarios = \App\Models\User::whereIn('role', ['admin','writer','reader'])->get();

        foreach ($items as $u) {
            $anterior = $u->estado_base ?? '021';

            // ⚙️ Forzamos base a 032; tu hook saving normaliza estado = irregular, limpia fecha_baja, etc.
            $u->estado_base = '032';
            // si querés garantizar el canónico aunque el hook falle:
            // $u->estado = 'irregular';
            $u->save();

            $nombre = $u->nombre_comercial
                ?: ($u->razon_social ?: trim(($u->apellido ?? '').' '.($u->nombres ?? '')));

            foreach ($destinatarios as $usr) {
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

        $this->info("Vencidos marcados y notificados: {$items->count()}");
        return self::SUCCESS;
    }
}
