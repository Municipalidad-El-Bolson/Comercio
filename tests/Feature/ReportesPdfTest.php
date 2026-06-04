<?php

namespace Tests\Feature;

use App\Http\Middleware\SingleSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
