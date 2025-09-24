<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ubicacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarcarIrregularesPorVencimiento extends Command
{
    protected $signature = 'ubicaciones:marcar-irregulares';
    protected $description = 'Pasa a estado irregular (032) las ubicaciones vigentes con fecha_vto vencida.';

    public function handle()
{
    $hoy = now()->startOfDay();

    Ubicacion::query()
        ->where('estado', 'vigente')               // sólo altas
        ->whereDate('fecha_vto', '<', $hoy)        // vencidas
        ->chunkById(500, function ($rows) {
            foreach ($rows as $u) {
                \DB::transaction(function () use ($u) {
                    if ($u->estado !== 'irregular') {
                        $u->estado = 'irregular';
                        $u->save();
                    }

                    $u->movimientos()->firstOrCreate(
                        ['etapa' => 'vencimiento'],
                        ['descripcion' => 'Paso automático a 032 por vencimiento.']
                    );
                });
            }
        });

    $this->info('Listo: estados marcados y movimientos registrados sin duplicar.');
}

}
