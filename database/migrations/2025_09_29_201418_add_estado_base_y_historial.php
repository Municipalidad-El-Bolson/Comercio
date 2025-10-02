<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('ubicaciones', function (Blueprint $t) {
      // nuevos campos “seguros” (dejamos el viejo estado por compatibilidad)
      $t->string('estado_base', 32)->nullable()->after('estado');     // 021 | 032 | baja | baja_oficio | exp_sin_efecto
      $t->string('estado_label', 120)->nullable()->after('estado_base'); // “021- Cambio de domicilio”
    });

    Schema::create('ubicacion_estado_historial', function (Blueprint $t) {
      $t->id();
      $t->unsignedBigInteger('ubicacion_id');
      $t->string('estado_base', 32);
      $t->string('estado_label', 120);
      $t->date('fecha_alta')->nullable();
      $t->date('fecha_baja')->nullable();
      $t->date('fecha_vto')->nullable();
      $t->unsignedBigInteger('user_id')->nullable(); // quién hizo el cambio
      $t->timestamps();
      $t->foreign('ubicacion_id')->references('id')->on('ubicaciones')->onDelete('cascade');
    });
  }

  public function down(): void {
    Schema::dropIfExists('ubicacion_estado_historial');
    Schema::table('ubicaciones', function (Blueprint $t) {
      if (Schema::hasColumn('ubicaciones','estado_label')) $t->dropColumn('estado_label');
      if (Schema::hasColumn('ubicaciones','estado_base'))  $t->dropColumn('estado_base');
    });
  }
};
