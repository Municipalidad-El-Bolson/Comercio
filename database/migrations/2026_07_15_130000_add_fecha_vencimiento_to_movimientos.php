<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->date('fecha_vencimiento')->nullable()->index()->after('fecha');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', fn (Blueprint $table) => $table->dropColumn('fecha_vencimiento'));
    }
};
