<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expediente_seguimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ubicacion_id')->index();
            $table->string('sector_desde')->nullable();
            $table->string('sector_hasta');
            $table->date('fecha');
            $table->text('observacion')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();
            $table->foreign('ubicacion_id')->references('id')->on('ubicaciones')->cascadeOnDelete();
        });

        if (!Schema::hasTable('movimientos')) return;

        $anteriores = [];
        DB::table('movimientos')->where('tipo', 'timeline')->whereNotNull('etapa')
            ->orderBy('ubicacion_id')->orderBy('fecha')->orderBy('id')->get()
            ->each(function ($movimiento) use (&$anteriores) {
                $ubicacionId = (int) $movimiento->ubicacion_id;
                DB::table('expediente_seguimientos')->insert([
                    'ubicacion_id' => $ubicacionId,
                    'sector_desde' => $anteriores[$ubicacionId] ?? null,
                    'sector_hasta' => $movimiento->etapa,
                    'fecha' => $movimiento->fecha ?: now()->toDateString(),
                    'observacion' => $movimiento->observacion ?? null,
                    'user_id' => null,
                    'created_at' => $movimiento->created_at ?? now(),
                    'updated_at' => $movimiento->updated_at ?? now(),
                ]);
                $anteriores[$ubicacionId] = $movimiento->etapa;
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('expediente_seguimientos');
    }
};
