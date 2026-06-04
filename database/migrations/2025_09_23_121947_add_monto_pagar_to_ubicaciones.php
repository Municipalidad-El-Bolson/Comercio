<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->decimal('monto_pagar', 12, 2)->nullable()->after('nomenclatura');
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->dropColumn('monto_pagar');
        });
    }
};