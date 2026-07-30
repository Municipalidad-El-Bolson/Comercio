<?php

namespace Tests\Feature;

use App\Http\Middleware\SingleSession;
use App\Livewire\Comercio\ActasSeguimiento;
use App\Models\Movimiento;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActasSeguimientoTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_solo_actas_con_vencimiento_ordenadas_por_prioridad(): void
    {
        $this->withoutMiddleware(SingleSession::class);
        $user = User::factory()->create(['role' => 'writer']);
        $ubicacion = Ubicacion::query()->create([
            'nombre_comercial' => 'Comercio inspeccionado', 'dni_cuit' => '20123456789',
            'estado' => 'entramite', 'estado_base' => '021', 'tipo_hab' => 'prev',
        ]);

        Movimiento::query()->create([
            'ubicacion_id' => $ubicacion->id, 'tipo' => 'acta', 'titulo' => 'Acta próxima',
            'fecha' => now(), 'fecha_vencimiento' => today()->addDays(10),
        ]);
        Movimiento::query()->create([
            'ubicacion_id' => $ubicacion->id, 'tipo' => 'acta', 'titulo' => 'Acta vencida',
            'fecha' => now(), 'fecha_vencimiento' => today()->subDay(),
        ]);
        Movimiento::query()->create([
            'ubicacion_id' => $ubicacion->id, 'tipo' => 'acta', 'titulo' => 'Acta sin plazo',
            'fecha' => now(),
        ]);

        Livewire::actingAs($user)->test(ActasSeguimiento::class)
            ->assertSeeInOrder(['Acta vencida', 'Acta próxima'])
            ->assertDontSee('Acta sin plazo');
    }

    public function test_permite_buscar_actas_por_hc_y_nombre_del_titular(): void
    {
        $this->withoutMiddleware(SingleSession::class);
        $user = User::factory()->create(['role' => 'writer']);

        $ubicacionBuscada = Ubicacion::query()->create([
            'hc' => '7777',
            'apellido' => 'Perez',
            'nombres' => 'Maria Laura',
            'nombre_comercial' => 'Kiosco Norte',
            'dni_cuit' => '20123456789',
            'estado' => 'entramite',
            'estado_base' => '021',
            'tipo_hab' => 'prev',
        ]);

        $otraUbicacion = Ubicacion::query()->create([
            'hc' => '8888',
            'apellido' => 'Gomez',
            'nombres' => 'Carlos',
            'nombre_comercial' => 'Despensa Sur',
            'dni_cuit' => '20987654321',
            'estado' => 'entramite',
            'estado_base' => '021',
            'tipo_hab' => 'prev',
        ]);

        Movimiento::query()->create([
            'ubicacion_id' => $ubicacionBuscada->id,
            'tipo' => 'acta',
            'titulo' => 'Acta buscada',
            'fecha' => now(),
            'fecha_vencimiento' => today()->addDays(5),
        ]);

        Movimiento::query()->create([
            'ubicacion_id' => $otraUbicacion->id,
            'tipo' => 'acta',
            'titulo' => 'Acta de otro comercio',
            'fecha' => now(),
            'fecha_vencimiento' => today()->addDays(5),
        ]);

        Livewire::actingAs($user)->test(ActasSeguimiento::class)
            ->set('search', '7777')
            ->assertSee('Acta buscada')
            ->assertSee('HC 7777')
            ->assertSee('Perez Maria Laura')
            ->assertDontSee('Acta de otro comercio');

        Livewire::actingAs($user)->test(ActasSeguimiento::class)
            ->set('search', 'Maria')
            ->assertSee('Acta buscada')
            ->assertDontSee('Acta de otro comercio');
    }
}
