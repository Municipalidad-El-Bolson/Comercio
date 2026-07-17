<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ComercioProfileViewTest extends TestCase
{
    public function test_perfil_encapsula_estilos_y_conecta_acciones_principales(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/livewire/comercio/comercio-data.blade.php');
        $component = file_get_contents(__DIR__.'/../../app/Livewire/Comercio/ComercioData.php');

        $this->assertStringContainsString('class="commerce-profile ', $view);
        $this->assertStringContainsString('.commerce-profile .card', $view);
        $this->assertStringNotContainsString("\n  .card {", $view);
        $this->assertStringContainsString('wire:click="abrirComunicacion"', $view);
        $this->assertStringContainsString('wire:key="timeline-comercio-', $view);
        $this->assertStringContainsString('wire:click="editaComercio(', $view);
        $this->assertStringContainsString('wire:click="mostrarMovimientos(', $view);
        $this->assertStringContainsString('public function abrirComunicacion(): void', $component);
    }

    public function test_campanas_tienen_ancho_estable_y_contador_limitado(): void
    {
        $bell = file_get_contents(__DIR__.'/../../resources/views/livewire/notifications/bell.blade.php');
        $sidebar = file_get_contents(__DIR__.'/../../resources/views/admin/layouts/side-nav.blade.php');

        $this->assertStringContainsString('sidebar-notification-count', $bell);
        $this->assertStringContainsString("\$unread > 99 ? '99+'", $bell);
        $this->assertStringContainsString('flex: 0 0 34px', $sidebar);
        $this->assertStringContainsString('pointer-events: none', $sidebar);
    }
}
