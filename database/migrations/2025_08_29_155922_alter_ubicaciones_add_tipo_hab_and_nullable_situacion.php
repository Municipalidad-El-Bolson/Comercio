<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->string('situacion')->nullable()->change();

            $table->enum('tipo_hab', ['definitiva','prev'])
                  ->default('prev')
                  ->after('estado');

            $table->date('fecha_vto')->nullable()->change();
        });

        DB::table('ubicaciones')
            ->whereNull('tipo_hab')
            ->update(['tipo_hab' => 'prev']);
    }

    public function down()
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->string('situacion')->nullable(false)->default('alta')->change();
            $table->dropColumn('tipo_hab');
        });
    }
};

