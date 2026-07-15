<?php

namespace Tests\Feature;

use App\Livewire\Comercio\MovimientoModal;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MovimientoModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_carga_un_acta_para_el_comercio_preseleccionado(): void
    {
        $user = User::factory()->create(['role' => 'writer']);
        $ubicacion = Ubicacion::query()->create([
            'nombre_comercial' => 'Comercio con acta',
            'dni_cuit' => '20123456789',
            'estado' => 'entramite',
            'estado_base' => '021',
            'tipo_hab' => 'prev',
        ]);

        Livewire::actingAs($user)->test(MovimientoModal::class)
            ->call('abrirModalMovimientos', $ubicacion->id)
            ->set('titulo', 'Inspección del local')
            ->set('tipo_acta', 'inspeccion')
            ->set('estado', 'Completo')
            ->set('descripcion', 'Sin observaciones')
            ->set('dias_vencimiento', 15)
            ->assertSee('Fecha de vencimiento:')
            ->call('guardarMovimiento')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('movimientos', [
            'ubicacion_id' => $ubicacion->id,
            'tipo' => 'acta',
            'tipo_acta' => 'inspeccion',
            'titulo' => 'Inspección Del Local',
            'estado' => 'Completo',
        ]);

        $this->assertSame(
            now()->startOfDay()->addDays(15)->toDateString(),
            \App\Models\Movimiento::query()->where('tipo', 'acta')->first()->fecha_vencimiento->format('Y-m-d')
        );
    }

    public function test_el_perfil_del_comercio_monta_el_modal_de_actas(): void
    {
        $view = file_get_contents(resource_path('views/livewire/comercio/comercio-data.blade.php'));

        $this->assertStringContainsString('<livewire:comercio.movimiento-modal', $view);
        $this->assertStringContainsString('wire:click="mostrarMovimientos(', $view);
        $this->assertStringContainsString('wire:key="acta-perfil-', $view);
        $this->assertStringContainsString('wire:click="$toggle(\'actasAbiertas\')"', $view);
    }
}
