<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AutosaveAssetsTest extends TestCase
{
    public function test_layouts_cargan_autoguardado_y_excluye_passwords_y_archivos(): void
    {
        $app = file_get_contents(__DIR__.'/../../resources/views/admin/layouts/app.blade.php');
        $mesa = file_get_contents(__DIR__.'/../../resources/views/admin/layouts/mesa.blade.php');
        $script = file_get_contents(__DIR__.'/../../public/js/livewire-autosave.js');

        $this->assertStringContainsString('livewire-autosave.js', $app);
        $this->assertStringContainsString('livewire-autosave.js', $mesa);
        $this->assertStringContainsString("el.type === 'password'", $script);
        $this->assertStringContainsString("el.type === 'file'", $script);
        $this->assertStringContainsString('localStorage.setItem', $script);
        $this->assertStringContainsString('Se recuperó un borrador', $script);
    }
}
