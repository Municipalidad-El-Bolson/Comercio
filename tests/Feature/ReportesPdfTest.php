<?php

namespace Tests\Feature;

use App\Livewire\Comercio\Reportes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportesPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_descargar_pdf_de_reportes(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($user)
            ->test(Reportes::class)
            ->call('exportarPdf')
            ->assertFileDownloaded();
    }
}
