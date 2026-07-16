<?php

namespace Tests\Feature;

use App\Livewire\MesaEntrada\Form;
use App\Livewire\MesaEntrada\Inbox;
use App\Livewire\MesaEntrada\Historial;
use App\Models\Documento;
use App\Models\MesaEntradaRegistro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use App\Notifications\MesaEntradaNotification;
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
            ->test(Historial::class)
            ->set('search', 'AFIP')
            ->assertSee('Titular buscable')
            ->assertSee('Constancia de AFIP');
    }

    public function test_mesa_de_entrada_tiene_buscador_independiente(): void
    {
        $user = User::factory()->create(['role' => 'writer']);
        $user->notify(new MesaEntradaNotification([
            'fecha' => '2026-07-16', 'nro_ingreso' => 901, 'docs' => ['Final de obra'],
            'titular' => 'Comercio Encontrado', 'hc' => 'HC-901', 'sender_name' => 'Mesa',
        ]));

        Livewire::actingAs($user)->test(Inbox::class)
            ->set('search', 'Final de obra')
            ->assertSee('Comercio Encontrado')
            ->set('search', 'inexistente')
            ->assertDontSee('Comercio Encontrado');
    }

    public function test_ambas_pantallas_exportan_excel_y_pdf_con_filtros(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\SingleSession::class);
        $user = User::factory()->create(['role' => 'writer']);
        $user->notify(new MesaEntradaNotification([
            'fecha' => '2026-07-16', 'nro_ingreso' => 902, 'docs' => ['DNI'],
            'titular' => 'Exportable', 'hc' => null, 'sender_name' => 'Mesa',
        ]));
        MesaEntradaRegistro::create([
            'fecha' => '2026-07-16', 'nro_ingreso' => 902, 'titular_razon' => 'Exportable',
            'documentos' => ['DNI'], 'sender_name' => 'Mesa',
        ]);

        $this->actingAs($user)->get(route('mesa.inbox.excel', ['search' => 'Exportable']))
            ->assertOk()->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $this->actingAs($user)->get(route('mesa.inbox.pdf', ['search' => 'Exportable']))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($user)->get(route('mesa.historial.excel', ['search' => 'Exportable']))
            ->assertOk()->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $this->actingAs($user)->get(route('mesa.historial.pdf', ['search' => 'Exportable']))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_importa_notificaciones_anteriores_sin_duplicarlas_por_destinatario(): void
    {
        $payload = [
            'fecha' => '2026-07-10',
            'nro_ingreso' => 777,
            'titular' => 'Histórico Municipal',
            'hc' => 'HC-10',
            'docs' => ['Certificado de salud'],
            'sender_name' => 'Mesa',
        ];

        foreach (range(1, 3) as $recipient) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => \App\Notifications\MesaEntradaNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $recipient,
                'data' => json_encode($payload),
                'created_at' => '2026-07-10 10:30:00',
                'updated_at' => '2026-07-10 10:30:00',
            ]);
        }

        $migration = require database_path('migrations/2026_07_16_130000_backfill_mesa_entrada_registros_from_notifications.php');
        $migration->up();

        $this->assertSame(1, MesaEntradaRegistro::where('nro_ingreso', 777)->count());
        $this->assertSame(['Certificado de salud'], MesaEntradaRegistro::where('nro_ingreso', 777)->first()->documentos);
    }
}
