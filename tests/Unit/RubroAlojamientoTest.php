<?php

namespace Tests\Unit;

use App\Models\Rubro;
use PHPUnit\Framework\TestCase;

class RubroAlojamientoTest extends TestCase
{
    public function test_detecta_alojamiento_con_acentos_espacios_y_mayusculas(): void
    {
        $rubro = new Rubro([
            'rubro_madre' => '  ALOJAMIENTO   DE ALQUILER TURÍSTICO ',
            'subrubro' => 'Cabañas',
        ]);

        $this->assertTrue($rubro->esAlojamientoTuristico());
        $this->assertFalse($rubro->esCamping());
    }

    public function test_distingue_camping_de_otro_alojamiento(): void
    {
        $rubro = new Rubro([
            'rubro_madre' => 'Alojamiento turístico',
            'subrubro' => 'Camping organizado',
        ]);

        $this->assertTrue($rubro->esAlojamientoTuristico());
        $this->assertTrue($rubro->esCamping());
    }
}
