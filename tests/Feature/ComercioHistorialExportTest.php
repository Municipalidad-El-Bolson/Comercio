<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Ubicacion;
use App\Models\User;
use App\Http\Middleware\SingleSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        $rubroAnterior = DB::table('rubros')->insertGetId([
            'mega_rubro' => 'COMERCIO', 'rubro_madre' => 'ANTERIOR', 'subrubro' => 'Kiosco',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $rubroNuevo = DB::table('rubros')->insertGetId([
            'mega_rubro' => 'COMERCIO', 'rubro_madre' => 'NUEVO', 'subrubro' => 'Almacén',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $audit = AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'Se modificó el comercio',
            'entity_type' => Ubicacion::class,
            'entity_id' => (string) $ubicacion->id,
            'meta' => [
                'action' => 'updated',
                'diff' => [
                    'domicilio_comercio' => [
                        'old' => 'Cerro Perito Moreno Cota 1365, R8430 El Bolsón, Río Negro, Argentina',
                        'new' => 'Cerro Perito Moreno Cota 1367, R8430 El Bolsón, Río Negro, Argentina',
                    ],
                    'barrio' => ['old' => 'Barrio anterior', 'new' => 'Barrio nuevo'],
                    'rubro_id' => ['old' => $rubroAnterior, 'new' => $rubroNuevo],
                    'fecha_alta' => ['old' => '2025-06-18T03:00:00.000000Z', 'new' => '2021-07-20T03:00:00.000000Z'],
                    'lat' => ['old' => -41.9, 'new' => -41.8],
                    'lng' => ['old' => -71.5, 'new' => -71.4],
                ],
            ],
        ]);

        $lines = $audit->fresh()->diff_lines;
        $this->assertCount(4, $lines);
        $this->assertStringContainsString('Domicilio', $lines[0]);
        $this->assertStringContainsString('Cota 1365 → Cerro Perito Moreno Cota 1367', $lines[0]);
        $this->assertStringNotContainsString('R8430', $lines[0]);
        $this->assertStringContainsString('Barrio', $lines[1]);
        $this->assertStringContainsString('Kiosco', $lines[2]);
        $this->assertStringContainsString('Almacén', $lines[2]);
        $this->assertStringContainsString('18/06/2025', $lines[3]);
        $this->assertStringContainsString('20/07/2021', $lines[3]);
        $this->assertStringNotContainsString('T03:00:00', $lines[3]);

        $excel = $this->actingAs($user)
            ->get(route('comercio.historial.excel', $ubicacion));

        $excel
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8')
            ->assertDownload();
        $this->assertStringContainsString('ss:Type="String">18/06/2025', $excel->streamedContent());
        $this->assertStringContainsString('ss:Type="String">20/07/2021', $excel->streamedContent());
        $this->assertStringNotContainsString('T03:00:00', $excel->streamedContent());

        $this->actingAs($user)
            ->get(route('comercio.historial.pdf', $ubicacion))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
