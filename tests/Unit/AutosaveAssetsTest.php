<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AutosaveAssetsTest extends TestCase
{
    public function test_autoguardado_ofrece_el_borrador_sin_restaurarlo_automaticamente(): void
    {
        $app = file_get_contents(__DIR__.'/../../resources/views/admin/layouts/app.blade.php');
        $mesa = file_get_contents(__DIR__.'/../../resources/views/admin/layouts/mesa.blade.php');
        $script = file_get_contents(__DIR__.'/../../public/js/livewire-autosave.js');

        $this->assertStringContainsString('livewire-autosave.js', $app);
        $this->assertStringContainsString('livewire-autosave.js', $mesa);
        $this->assertStringContainsString("el.type === 'password'", $script);
        $this->assertStringContainsString("el.type === 'file'", $script);
        $this->assertStringContainsString('localStorage.setItem', $script);
        $this->assertStringContainsString('allowedModels.has(model)', $script);
        $this->assertStringContainsString("key.startsWith('comercio:draft:v1:')", $script);
        $this->assertStringContainsString('roots().forEach(offerRestore)', $script);
        $this->assertStringContainsString('Recuperar borrador', $script);
        $this->assertStringNotContainsString('roots().forEach(restore)', $script);
    }
}
