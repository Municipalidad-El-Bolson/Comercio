<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Agregar/ajustar columnas nuevas
        Schema::table('ubicaciones', function (Blueprint $table) {
            // Identidad
            $table->string('persona_tipo')->default('fisica'); // 'fisica' | 'juridica'
            $table->string('dni_cuit');

            // Datos de contacto y comercio
            $table->string('domicilio_responsable');
            $table->string('correo')->nullable();
            $table->string('telefono')->nullable();
            $table->string('nombre_comercial')->nullable();
            $table->string('domicilio_comercio'); // sustituye a 'direccion'
            $table->string('nomenclatura')->nullable();
            $table->text('observaciones')->nullable();

            // Estado del trámite (manteniendo tus valores)
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->enum('estado', ['vigente','irregular','entramite'])->default('vigente')->change();
            } else {
                $table->string('estado')->default('vigente')->change();
            }

            // Alta/Baja + fechas
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->enum('situacion', ['alta','baja'])->default('alta');
            } else {
                $table->string('situacion')->default('alta');
            }
            $table->date('fecha_alta')->nullable();
            $table->date('fecha_baja')->nullable();
        });

        // 2) Migrar datos desde 'direccion' → 'domicilio_comercio' si existe
        if (Schema::hasColumn('ubicaciones', 'direccion')) {
            DB::statement("UPDATE ubicaciones SET domicilio_comercio = direccion WHERE domicilio_comercio IS NULL OR domicilio_comercio = ''");
            // 3) Borrar columna antigua (evitamos rename para no requerir doctrine/dbal)
            Schema::table('ubicaciones', function (Blueprint $table) {
                $table->dropColumn('direccion');
            });
        }

        // 4) Eliminar 'tipo' si aún existe
        if (Schema::hasColumn('ubicaciones', 'tipo')) {
            Schema::table('ubicaciones', function (Blueprint $table) {
                $table->dropColumn('tipo');
            });
        }
    }

    public function down(): void
    {
        // 1) Restaurar 'direccion' y copiar desde 'domicilio_comercio'
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->string('direccion')->nullable();
        });

        DB::statement("UPDATE ubicaciones SET direccion = domicilio_comercio WHERE domicilio_comercio IS NOT NULL AND domicilio_comercio != ''");

        // 2) Quitar columnas nuevas y restaurar 'tipo'
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->dropColumn([
                'persona_tipo', 'dni_cuit', 'domicilio_responsable', 'correo', 'telefono',
                'nombre_comercial', 'domicilio_comercio', 'nomenclatura', 'observaciones',
                'situacion', 'fecha_alta', 'fecha_baja'
            ]);

            // si querés recuperar 'tipo' (opcional):
            $table->string('tipo')->nullable();
        });

        // 3) (Opcional) revertir cambio de 'estado' según tu necesidad
        // aquí lo dejamos como está para simplificar
    }
};
