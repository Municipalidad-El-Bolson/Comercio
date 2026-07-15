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

        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'Se modificó el comercio',
            'entity_type' => Ubicacion::class,
            'entity_id' => (string) $ubicacion->id,
            'meta' => [
                'action' => 'updated',
                'diff' => ['nombre_comercial' => ['old' => 'Anterior', 'new' => 'Comercio auditado']],
            ],
        ]);

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
