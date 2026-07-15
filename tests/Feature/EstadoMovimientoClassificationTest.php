<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\Ubicacion;
use Tests\TestCase;

class EstadoMovimientoClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_migracion_reclasifica_solo_movimientos_vacios_de_estado(): void
    {
        $migration = require database_path('migrations/2026_07_15_140000_reclassify_estado_movimientos.php');
        $ubicacion = Ubicacion::query()->create([
            'nombre_comercial' => 'Comercio', 'dni_cuit' => '20123456789',
            'estado' => 'entramite', 'estado_base' => '021', 'tipo_hab' => 'prev',
        ]);

        DB::table('movimientos')->insert([
            ['ubicacion_id' => $ubicacion->id, 'tipo' => 'acta', 'etapa' => 'estado', 'titulo' => null, 'tipo_acta' => null, 'estado' => 'En Proceso'],
            ['ubicacion_id' => $ubicacion->id, 'tipo' => 'acta', 'etapa' => null, 'titulo' => 'Acta real', 'tipo_acta' => 'inspeccion', 'estado' => 'En Proceso'],
        ]);

        $migration->up();

        $this->assertDatabaseHas('movimientos', ['titulo' => null, 'etapa' => 'estado', 'tipo' => 'estado']);
        $this->assertDatabaseHas('movimientos', ['titulo' => 'Acta real', 'tipo' => 'acta']);
    }
}
