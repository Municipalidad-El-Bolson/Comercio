<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Ubicacion;
use App\Models\User;
use App\Http\Middleware\SingleSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComercioHistorialExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_writer_puede_exportar_historial_a_excel_y_pdf(): void
    {
        $this->withoutMiddleware(SingleSession::class);
        $user = User::factory()->create(['role' => 'writer', 'current_session_id' => null]);
        $ubicacion = Ubicacion::query()->create([
            'nombre_comercial' => 'Comercio auditado',
            'dni_cuit' => '20123456789',
            'estado' => 'entramite',
            'estado_base' => '021',
            'tipo_hab' => 'prev',
        ]);

        $audit = AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'Se modificó el comercio',
            'entity_type' => Ubicacion::class,
            'entity_id' => (string) $ubicacion->id,
            'meta' => [
                'action' => 'updated',
                'diff' => [
                    'domicilio_comercio' => ['old' => 'Dirección anterior', 'new' => 'Dirección nueva'],
                    'barrio' => ['old' => 'Barrio anterior', 'new' => 'Barrio nuevo'],
                    'lat' => ['old' => -41.9, 'new' => -41.8],
                    'lng' => ['old' => -71.5, 'new' => -71.4],
                ],
            ],
        ]);

        $lines = $audit->fresh()->diff_lines;
        $this->assertCount(2, $lines);
        $this->assertStringContainsString('Domicilio', $lines[0]);
        $this->assertStringContainsString('Barrio', $lines[1]);

        $this->actingAs($user)
            ->get(route('comercio.historial.excel', $ubicacion))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload();

        $this->actingAs($user)
            ->get(route('comercio.historial.pdf', $ubicacion))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
