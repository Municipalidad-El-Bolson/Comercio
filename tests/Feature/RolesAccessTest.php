<?php

namespace Tests\Feature;

use App\Http\Middleware\SingleSession;
use App\Livewire\Comercio\ComercioData;
use App\Livewire\Comercio\MovimientoModal;
use App\Livewire\Comercio\Timeline;
use App\Livewire\Comercio\Ubicaciones;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class RolesAccessTest extends TestCase
{
    use RefreshDatabase;

    private function comercio(): Ubicacion
    {
        return Ubicacion::create([
            'nombre_comercial' => 'Comercio inspeccionable', 'dni_cuit' => '20111111111',
            'estado' => 'entramite', 'estado_base' => '021', 'tipo_hab' => 'prev',
        ]);
    }

    public function test_permisos_de_usuario_administrativo_e_inspector(): void
    {
        $administrativo = User::factory()->create(['role' => 'writer']);
        $inspector = User::factory()->create(['role' => 'reader']);

        $this->assertTrue(Gate::forUser($administrativo)->allows('administrative-user'));
        $this->assertTrue(Gate::forUser($administrativo)->allows('manage-ubicaciones'));
        $this->assertTrue(Gate::forUser($administrativo)->allows('manage-actas'));
        $this->assertFalse(Gate::forUser($administrativo)->allows('access-admin'));

        $this->assertTrue(Gate::forUser($inspector)->allows('view-ubicaciones'));
        $this->assertTrue(Gate::forUser($inspector)->allows('manage-actas'));
        $this->assertFalse(Gate::forUser($inspector)->allows('manage-ubicaciones'));
        $this->assertFalse(Gate::forUser($inspector)->allows('mesa-entrada-view'));
    }

    public function test_inspector_accede_a_mapa_comercios_y_perfil_pero_no_a_secciones_administrativas(): void
    {
        $this->withoutVite();
        $this->withoutMiddleware(SingleSession::class);
        $inspector = User::factory()->create(['role' => 'reader']);
        $comercio = $this->comercio();

        $this->actingAs($inspector)->get(route('mapas'))->assertOk();
        $this->actingAs($inspector)->get(route('ubicaciones'))->assertOk();
        $this->actingAs($inspector)->get(route('comercio.data', $comercio))->assertOk();
        $this->actingAs($inspector)->get(route('mesa.inbox'))->assertForbidden();
        $this->actingAs($inspector)->get(route('prox_vto.index'))->assertForbidden();
        $this->actingAs($inspector)->get(route('users.index'))->assertForbidden();

        Livewire::actingAs($inspector)->test(ComercioData::class, ['ubicacion' => $comercio])
            ->assertSee('Nueva acta')
            ->assertSee(route('ubicaciones'), false)
            ->assertDontSee('Eliminar definitivamente este comercio');
    }

    public function test_inspector_puede_cargar_actas_pero_no_editar_comercios_ni_etapas(): void
    {
        $inspector = User::factory()->create(['role' => 'reader']);
        $comercio = $this->comercio();

        Livewire::actingAs($inspector)->test(MovimientoModal::class)
            ->call('abrirModalMovimientos', $comercio->id)
            ->set('titulo', 'Acta del inspector')
            ->set('tipo_acta', 'inspeccion')
            ->set('estado', 'En Proceso')
            ->call('guardarMovimiento')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('movimientos', [
            'ubicacion_id' => $comercio->id, 'tipo' => 'acta', 'titulo' => 'Acta Del Inspector',
        ]);

        Livewire::actingAs($inspector)->test(Ubicaciones::class)
            ->call('nuevoComercio')->assertForbidden();

        Livewire::actingAs($inspector)->test(Timeline::class, ['ubicacionId' => $comercio->id])
            ->set('etapaActual', 'inspeccion')->call('guardarEtapa')->assertForbidden();
    }

    public function test_nombres_nuevos_de_roles_se_muestran_en_usuarios(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'writer']);
        User::factory()->create(['role' => 'reader']);

        Livewire::actingAs($admin)->test(\App\Livewire\Admin\UsersIndex::class)
            ->assertSee('Usuario administrativo')
            ->assertSee('Inspector');
    }
}
