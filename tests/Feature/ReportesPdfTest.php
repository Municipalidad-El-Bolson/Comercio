<?php

namespace Tests\Feature;

use App\Livewire\Comercio\Reportes;
use App\Http\Middleware\SingleSession;
use App\Models\User;
use App\Models\Rubro;
use App\Models\Ubicacion;
use App\Models\UbicacionEstadoHist;
use App\Models\UbicacionTelefono;
use App\Support\ReportesComercioData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportesPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_reportes_no_usa_autoguardado_para_los_filtros(): void
    {
        $view = file_get_contents(resource_path('views/livewire/comercio/reportes.blade.php'));

        $this->assertStringContainsString('<section class="content" data-autosave="off">', $view);
    }

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
            ->assertSet('desde', null)
            ->assertSet('hasta', null)
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

        $item = app(ReportesComercioData::class)->formatItem($ubicacion);

        $this->assertSame('Pérez Ana María', $item['titular']);
        $this->assertSame('Cambio de Rubro', $item['tramite']);
        $this->assertSame('20/06/2026', $item['fecha_asociada']);
        $this->assertSame('San Martín 123', $item['direccion']);
        $this->assertSame('01/07/2026', $item['suspension_desde']);
        $this->assertSame('31/07/2026', $item['suspension_hasta']);
        $this->assertStringContainsString('Hostería', $item['rubros']);
    }

    public function test_alojamientos_sin_fecha_se_exportan_con_unidades_y_plazas(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $rubro = (new Rubro())->forceFill([
            'rubro_general' => 'ALOJAMIENTO DE ALQUILER TURISTICO',
            'mega_rubro' => 'SERVICIOS',
            'rubro_madre' => 'ALOJAMIENTO TURISTICO',
            'subrubro' => 'CABANA',
        ]);
        $rubro->save();

        $ubicacion = Ubicacion::create([
            'persona_tipo' => 'fisica', 'apellido' => 'Lopez', 'nombres' => 'Maria',
            'dni_cuit' => '12345678',
            'rubro_id' => $rubro->id, 'estado' => '021', 'estado_base' => '021',
            'alojamiento_unidades' => 6, 'alojamiento_plazas' => 18,
        ]);
        $ubicacion->rubros()->sync([$rubro->id]);

        $items = app(ReportesComercioData::class)->items([
            'rubro_general' => 'ALOJAMIENTO DE ALQUILER TURISTICO',
        ]);

        $this->assertCount(1, $items);
        $this->assertNull($items->first()['fecha_asociada_raw']);
        $this->assertSame(6, $items->first()['unidades']);
        $this->assertSame(18, $items->first()['plazas']);

        $response = $this->withoutMiddleware(SingleSession::class)->actingAs($user)
            ->get(route('reportes.excel', ['rubroGeneral' => 'ALOJAMIENTO DE ALQUILER TURISTICO']));

        $response->assertOk()->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('CABANA', $content);
        $this->assertStringContainsString('ss:Type="Number">6', $content);
        $this->assertStringContainsString('ss:Type="Number">18', $content);
    }
}
