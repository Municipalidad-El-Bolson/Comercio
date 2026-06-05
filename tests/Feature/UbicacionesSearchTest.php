<?php

namespace Tests\Feature;

use App\Livewire\Comercio\Ubicaciones;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class UbicacionesSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_buscar_por_numero_hc(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $rubroId = DB::table('rubros')->insertGetId($this->onlyExisting('rubros', [
            'mega_rubro' => 'COMERCIO',
            'rubro_madre' => 'COMERCIO',
            'rubro' => 'Prueba',
            'subrubro' => 'Prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        DB::table('ubicaciones')->insert($this->onlyExisting('ubicaciones', [
            'hc' => 'HC-12345',
            'razon_social' => 'Comercio HC Encontrado',
            'nombre_comercial' => 'Comercio HC Encontrado',
            'apellido' => 'Perez',
            'nombres' => 'Juan',
            'dni' => 12345678,
            'dni_cuit' => '20-12345678-9',
            'rubro_id' => $rubroId,
            'direccion' => 'Av. Siempre Viva 123',
            'domicilio_comercio' => 'Av. Siempre Viva 123',
            'tipo' => 'local',
            'estado' => 'entramite',
            'estado_base' => '021',
            'situacion' => 'alta',
            'tipo_hab' => 'prev',
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        Livewire::actingAs($user)
            ->test(Ubicaciones::class)
            ->set('searchTerm', 'HC-12345')
            ->assertSee('Comercio HC Encontrado');
    }

    private function onlyExisting(string $table, array $values): array
    {
        $columns = Schema::getColumnListing($table);

        return array_intersect_key($values, array_flip($columns));
    }
}
