<?php

namespace Tests\Feature;

use App\Livewire\Comercio\Timeline;
use App\Models\Ubicacion;
use App\Models\User;
use App\Models\MesaEntradaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpedienteSeguimientoTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_sector_anterior_nuevo_fecha_usuario_y_observacion(): void
    {
        $user = User::factory()->create(['role' => 'writer']);
        $ubicacion = Ubicacion::query()->create([
            'nombre_comercial' => 'Comercio seguido',
            'dni_cuit' => '20123456789',
            'estado' => 'entramite',
            'estado_base' => '021',
            'tipo_hab' => 'prev',
        ]);

        Livewire::actingAs($user)->test(Timeline::class, ['ubicacionId' => $ubicacion->id])
            ->set('etapaActual', 'inspeccion')
            ->set('fechaManual', '2026-07-15')
            ->set('obs', 'Expediente enviado a inspección')
            ->call('guardarEtapa');

        $this->assertDatabaseHas('expediente_seguimientos', [
            'ubicacion_id' => $ubicacion->id,
            'sector_desde' => null,
            'sector_hasta' => 'inspeccion',
            'observacion' => 'Expediente enviado a inspección',
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)->test(Timeline::class, ['ubicacionId' => $ubicacion->id])
            ->set('etapaActual', 'control_expediente')
            ->set('fechaManual', '2026-07-16')
            ->call('guardarEtapa');

        $this->assertDatabaseHas('expediente_seguimientos', [
            'ubicacion_id' => $ubicacion->id,
            'sector_desde' => 'inspeccion',
            'sector_hasta' => 'control_expediente',
            'user_id' => $user->id,
        ]);

        $this->assertSame(
            '2026-07-16',
            \App\Models\ExpedienteSeguimiento::query()->latest('id')->first()->fecha->format('Y-m-d')
        );
    }

    public function test_muestra_observaciones_de_mesa_vinculadas_por_hc(): void
    {
        $user = User::factory()->create(['role' => 'writer']);
        $ubicacion = Ubicacion::query()->create([
            'hc' => 'HC-OBS-1', 'nombre_comercial' => 'Comercio observado',
            'dni_cuit' => '20999999999', 'estado' => 'entramite', 'estado_base' => '021', 'tipo_hab' => 'prev',
        ]);
        MesaEntradaRegistro::create([
            'fecha' => '2026-07-16', 'nro_ingreso' => 500,
            'titular_razon' => 'Titular', 'hc' => 'HC-OBS-1',
            'documentos' => ['DNI'], 'observacion' => 'Documentación sujeta a verificación',
        ]);

        Livewire::actingAs($user)->test(Timeline::class, ['ubicacionId' => $ubicacion->id])
            ->assertSee('Expediente Nº 500')
            ->assertSee('Documentación sujeta a verificación');
    }
}
