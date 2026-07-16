<?php

namespace Tests\Feature;

use App\Livewire\MesaEntrada\Form;
use App\Livewire\MesaEntrada\Inbox;
use App\Models\Documento;
use App\Models\MesaEntradaRegistro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MesaEntradaHistorialTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_envio_crea_un_registro_historico_permanente(): void
    {
        $mesa = User::factory()->create(['role' => 'mesa']);
        $documento = Documento::create(['nombre' => 'Certificado de libre deuda', 'activo' => true]);

        Livewire::actingAs($mesa)
            ->test(Form::class)
            ->set('fecha', '2026-07-16')
            ->set('nro_ingreso', 321)
            ->set('titular_razon', 'Comercio del Sur')
            ->set('hc', 'HC-88')
            ->set('documentacion_ids', [$documento->id])
            ->call('submit')
            ->assertHasNoErrors();

        $registro = MesaEntradaRegistro::firstOrFail();

        $this->assertSame(321, $registro->nro_ingreso);
        $this->assertSame('Comercio del Sur', $registro->titular_razon);
        $this->assertSame(['Certificado de libre deuda'], $registro->documentos);
        $this->assertSame($mesa->id, $registro->user_id);
    }

    public function test_el_historial_se_puede_buscar_por_documentacion(): void
    {
        $user = User::factory()->create(['role' => 'writer']);
        MesaEntradaRegistro::create([
            'fecha' => '2026-07-16',
            'nro_ingreso' => 654,
            'titular_razon' => 'Titular buscable',
            'hc' => 'HC-99',
            'documentos' => ['Constancia de AFIP'],
            'user_id' => $user->id,
            'sender_name' => $user->name,
        ]);

        Livewire::actingAs($user)
            ->test(Inbox::class)
            ->set('search', 'AFIP')
            ->assertSee('Titular buscable')
            ->assertSee('Constancia de AFIP');
    }
}
