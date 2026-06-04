<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('ubicaciones','latitud') && !Schema::hasColumn('ubicaciones','lat')) {
            Schema::table('ubicaciones', fn(Blueprint $t)=> $t->renameColumn('latitud','lat'));
        }
        if (Schema::hasColumn('ubicaciones','longitud') && !Schema::hasColumn('ubicaciones','lng')) {
            Schema::table('ubicaciones', fn(Blueprint $t)=> $t->renameColumn('longitud','lng'));
        }

        Schema::table('ubicaciones', function (Blueprint $t) {
            if (!Schema::hasColumn('ubicaciones','lat')) $t->decimal('lat',10,7)->nullable()->after('domicilio_comercio');
            if (!Schema::hasColumn('ubicaciones','lng')) $t->decimal('lng',10,7)->nullable()->after('lat');

            if (!Schema::hasColumn('ubicaciones','barrio')) {
                $t->string('barrio',120)->nullable()->after('nomenclatura');
                $t->index('barrio','ubic_barrio_idx');
            }

            if (!Schema::hasColumn('ubicaciones','cpu_cod')) {
                $t->string('cpu_cod',40)->nullable()->after('barrio');
                $t->index('cpu_cod','ubic_cpu_cod_idx');
            }

            if (!Schema::hasColumn('ubicaciones','cpu_nombre')) {
                $t->string('cpu_nombre',160)->nullable()->after('cpu_cod');
            }
        });

        if (Schema::hasColumn('ubicaciones','barrio') && Schema::hasColumn('ubicaciones','cpu_cod')) {
            Schema::table('ubicaciones', fn(Blueprint $t)=> $t->index(['barrio','cpu_cod'],'ubic_barrio_cpu_idx'));
        }
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $t) {
            try { $t->dropIndex('ubic_barrio_cpu_idx'); } catch (\Throwable) {}
            try { $t->dropIndex('ubic_barrio_idx'); } catch (\Throwable) {}
            try { $t->dropIndex('ubic_cpu_cod_idx'); } catch (\Throwable) {}

            if (Schema::hasColumn('ubicaciones','cpu_nombre')) $t->dropColumn('cpu_nombre');
            if (Schema::hasColumn('ubicaciones','cpu_cod'))    $t->dropColumn('cpu_cod');
            if (Schema::hasColumn('ubicaciones','barrio'))     $t->dropColumn('barrio');
            if (Schema::hasColumn('ubicaciones','lng'))        $t->dropColumn('lng');
            if (Schema::hasColumn('ubicaciones','lat'))        $t->dropColumn('lat');
        });
    }
};
