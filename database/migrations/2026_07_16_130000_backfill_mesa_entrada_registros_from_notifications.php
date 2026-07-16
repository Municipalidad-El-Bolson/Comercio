<?php

use App\Notifications\MesaEntradaNotification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $seen = [];

        DB::table('notifications')
            ->where('type', MesaEntradaNotification::class)
            ->orderBy('created_at')
            ->chunk(200, function ($notifications) use (&$seen): void {
                foreach ($notifications as $notification) {
                    $data = json_decode($notification->data, true) ?: [];

                    // Los envíos nuevos ya tienen su registro permanente.
                    if (!empty($data['registro_id'])) {
                        continue;
                    }

                    $docs = array_values(array_filter((array) ($data['docs'] ?? [])));
                    $signature = hash('sha256', json_encode([
                        $data['fecha'] ?? null,
                        $data['nro_ingreso'] ?? null,
                        $data['titular'] ?? null,
                        $data['hc'] ?? null,
                        $docs,
                        $data['sender_name'] ?? null,
                        (string) $notification->created_at,
                    ]));

                    if (isset($seen[$signature])) {
                        continue;
                    }
                    $seen[$signature] = true;

                    if (empty($data['fecha']) || empty($data['nro_ingreso']) || empty($data['titular'])) {
                        continue;
                    }

                    $exists = DB::table('mesa_entrada_registros')
                        ->whereDate('fecha', $data['fecha'])
                        ->where('nro_ingreso', $data['nro_ingreso'])
                        ->where('titular_razon', $data['titular'])
                        ->where('created_at', $notification->created_at)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    DB::table('mesa_entrada_registros')->insert([
                        'fecha' => $data['fecha'],
                        'nro_ingreso' => $data['nro_ingreso'],
                        'titular_razon' => $data['titular'],
                        'hc' => $data['hc'] ?? null,
                        'documentos' => json_encode($docs, JSON_UNESCAPED_UNICODE),
                        'user_id' => null,
                        'sender_name' => $data['sender_name'] ?? 'Mesa de Entrada',
                        'created_at' => $notification->created_at,
                        'updated_at' => $notification->created_at,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Los registros importados son información histórica y no se eliminan.
    }
};
