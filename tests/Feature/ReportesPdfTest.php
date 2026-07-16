<?php

namespace Tests\Feature;

use App\Livewire\Comercio\Reportes;
use App\Http\Middleware\SingleSession;
use App\Models\User;
use App\Models\Rubro;
use App\Models\Ubicacion;
use App\Models\UbicacionEstadoHist;
use App\Models\UbicacionTelefono;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportesPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_descargar_pdf_de_reportes(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this
            ->withoutMiddleware(SingleSession::class)
            ->actingAs($user)
            ->get(route('reportes.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition');
    }

    public function test_admin_puede_seleccionar_todos_en_proximos_vencimientos(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($user)
            ->test(Reportes::class)
            ->set('proximosVtos', '')
            ->assertSet('proximosVtos', null)
            ->assertOk();
    }

    public function test_pdf_formatea_datos_completos_y_fecha_del_tramite(): void
    {
        $rubro = new Rubro([
            'mega_rubro' => 'Servicios',
            'rubro_madre' => 'Alojamiento turístico',
            'subrubro' => 'Hostería',
        ]);
        $rubro->id = 10;

        $ubicacion = new Ubicacion([
            'hc' => 'HC-123',
            'apellido' => 'Pérez',
            'nombres' => 'Ana María',
            'domicilio_comercio' => 'San Martín 123, R8430 El Bolsón, Río Negro, Argentina',
            'alojamiento_unidades' => 4,
            'alojamiento_plazas' => 12,
            'situacion' => 'alta',
            'suspension_tasas_desde' => '2026-07-01',
            'suspension_tasas_hasta' => '2026-07-31',
        ]);
        $ubicacion->setRelation('rubro', $rubro);
        $ubicacion->setRelation('rubros', collect([$rubro]));
        $ubicacion->setRelation('telefonos', collect([new UbicacionTelefono(['telefono' => '2944 123456'])]));
        $ubicacion->setRelation('estadosHistorial', collect([new UbicacionEstadoHist([
            'estado_base' => '032',
            'estado_label' => '032 - Cambio de Rubro',
            'fecha_alta' => '2026-06-20',
        ])]));

        $controller = app(\App\Http\Controllers\Comercio\ReportesPdfController::class);
        $method = new \ReflectionMethod($controller, 'formatItem');
        $item = $method->invoke($controller, $ubicacion);

        $this->assertSame('Pérez Ana María', $item['titular']);
        $this->assertSame('Cambio de Rubro', $item['tramite']);
        $this->assertSame('20/06/2026', $item['fecha_asociada']);
        $this->assertSame('San Martín 123', $item['direccion']);
        $this->assertSame('01/07/2026', $item['suspension_desde']);
        $this->assertSame('31/07/2026', $item['suspension_hasta']);
        $this->assertStringContainsString('Hostería', $item['rubros']);
    }
}
