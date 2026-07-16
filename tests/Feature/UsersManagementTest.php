<?php

namespace Tests\Feature;

use App\Livewire\Admin\UsersIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UsersManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_crea_usuario_normalizando_nombre_y_correo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(UsersIndex::class)
            ->call('create')
            ->assertSet('showForm', true)
            ->set('name', '  María   Pérez  ')
            ->set('email', '  MARIA@EJEMPLO.COM ')
            ->set('role', 'writer')
            ->set('password', 'segura123')
            ->set('password_confirmation', 'segura123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('autosave-clear')
            ->assertSet('showForm', false);

        $usuario = User::where('email', 'maria@ejemplo.com')->firstOrFail();
        $this->assertSame('María Pérez', $usuario->name);
        $this->assertTrue(Hash::check('segura123', $usuario->password));
    }

    public function test_no_permite_correo_duplicado_ni_quitar_el_ultimo_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@ejemplo.com']);
        User::factory()->create(['role' => 'reader', 'email' => 'usado@ejemplo.com']);

        Livewire::actingAs($admin)->test(UsersIndex::class)
            ->call('create')->set('name', 'Duplicado')->set('email', 'USADO@EJEMPLO.COM')
            ->set('role', 'reader')->set('password', 'segura123')->set('password_confirmation', 'segura123')
            ->call('save')->assertHasErrors(['email']);

        Livewire::actingAs($admin)->test(UsersIndex::class)
            ->call('edit', $admin->id)->set('role', 'reader')->call('save')
            ->assertHasErrors(['role']);

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_edita_usuario_sin_obligar_a_cambiar_contrasena(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $usuario = User::factory()->create(['role' => 'reader', 'password' => Hash::make('original123')]);

        Livewire::actingAs($admin)->test(UsersIndex::class)
            ->call('edit', $usuario->id)
            ->set('name', 'Nombre actualizado')
            ->set('role', 'writer')
            ->call('save')->assertHasNoErrors();

        $this->assertSame('writer', $usuario->fresh()->role);
        $this->assertTrue(Hash::check('original123', $usuario->fresh()->password));
    }
}
