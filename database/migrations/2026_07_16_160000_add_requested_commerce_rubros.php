<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $rubros = [
            'ACCESORIOS PARA CELULARES', 'ALIMENTOS BALANCEADOS', 'ALQUILER DE EQUIPO DE MONTAÑA - NIEVE',
            'ALQUILER DE INDUMENTARIA DE SKI', 'ALQUILER DE SKI', 'ALQUILER DE TRINEOS', 'ARMERÍA',
            'ARTÍCULOS DE DESPENSA', 'AULA', 'BANCO COMERCIAL', 'BEBIDAS HÍDRICAS',
            'BODY PIERCING - PERFORACIONES Y COLOCACIÓN DE AROS', 'CAJEROS AUTOMÁTICOS', 'CAJEROS DE AUTOGESTIÓN',
            'CARPINTERÍA', 'CENTRO ADMINISTRATIVO', 'CENTRO DE EDUCACIÓN PRIVADA', 'CENTRO DE TERAPIAS ALTERNATIVAS',
            'CENTRO DIAGNÓSTICO POR IMAGEN', 'CERVECERÍA', 'CHURRERÍA', 'CHURRERÍA MÓVIL', 'COCINA Y DEPENDENCIAS',
            'COMPLEJO DEPORTIVO (CANCHA)', 'COPETÍN AL PASO', 'DEPÓSITO DE PRODUCTOS ALIMENTICIOS',
            'DEPÓSITO DE ENCOMIENDAS', 'DESTILERÍA', 'DIETÉTICA', 'DISEÑO GRÁFICO', 'ELABORACIÓN DE EMPANADAS',
            'ELABORACIÓN DE PASTAS CASERAS', 'ELABORACIÓN DE SUSHI', 'ELABORACIÓN DE HELADOS',
            'EMPRESA CONSTRUCTORA', 'EMPRESA DE SERVICIOS', 'ENTIDAD COMERCIAL', 'ENVASADORA DE AGUA',
            'ESCUELA DE NIVEL INICIAL', 'ESCUELA PRIMARIA', 'FÁBRICA DE BLOQUES', 'FÁBRICA DE DULCES',
            'FÁBRICA DE HELADOS', 'FÁBRICA DE PREMOLDEADOS', 'FORRAJERÍA', 'FOTOGRAFÍA INSTANTÁNEA EN LUGAR FIJO',
            'GESTIÓN DE PRÉSTAMOS MONETARIOS', 'GIMNASIO', 'GIMNASIO Y REHABILITACIÓN DEPORTIVA', 'GRÁFICA',
            'INDUMENTARIA DE PESCA', 'INDUMENTARIA Y ACCESORIOS PARA MOTOS', 'JUGUETERÍA', 'LAVADERO DE ROPA',
            'LIBRERÍA', 'MOVIMIENTO DE SUELO', 'MUTUAL', 'NATATORIO', 'OFICINA', 'OFICINA DE EMPRESA CONSTRUCTORA',
            'OFICINA DE TRÁMITES', 'OFICINA DE TRÁMITES (GESTIONES Y ENCOMIENDAS)', 'OFICINA VENTA DE SEGUROS',
            'ÓPTICA Y CONTACTOLOGÍA', 'PATIO DE COMIDAS', 'PELUQUERÍA CON VENTA DE PRODUCTOS AFINES', 'REGIONALES',
            'REPUESTOS DE REFRIGERACIÓN', 'SALA DE EMPAQUE DE FRUTAS FINAS', 'SALÓN DE EVENTOS',
        ];

        foreach ($rubros as $subrubro) {
            $general = $this->general($subrubro);
            $existente = DB::table('rubros')->whereRaw('LOWER(subrubro) = ?', [mb_strtolower($subrubro)])->first();

            if ($existente) {
                DB::table('rubros')->where('id', $existente->id)->update([
                    'rubro_general' => $general,
                    'updated_at' => now(),
                ]);
                continue;
            }

            DB::table('rubros')->insert([
                'mega_rubro' => 'CICI 2025',
                'rubro_madre' => 'RUBRO ÚNICO',
                'subrubro' => $subrubro,
                'rubro_general' => $general,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function general(string $rubro): string
    {
        $rubro = mb_strtoupper($rubro);

        if (preg_match('/CHURRER|COPETÍN|PATIO DE COMIDAS|CERVECERÍA|EMPANADAS|PASTAS|SUSHI|HELADOS|DESPENSA|DIETÉTICA|BEBIDAS/', $rubro)) return 'GASTRONOMIA';
        if (preg_match('/DIAGNÓSTICO|TERAPIAS|ÓPTICA|REHABILITACIÓN/', $rubro)) return 'SALUD';
        if (preg_match('/GIMNASIO|NATATORIO/', $rubro)) return 'GIMNASIOS';
        if (preg_match('/COMPLEJO DEPORTIVO|CANCHA/', $rubro)) return 'ALQUILER DE CANCHAS';
        if (preg_match('/PIERCING|PELUQUERÍA/', $rubro)) return 'CENTRO DE ESTETICA Y SPA';
        if (preg_match('/ELABORACIÓN|FÁBRICA DE DULCES|FÁBRICA DE HELADOS|ENVASADORA|EMPAQUE/', $rubro)) return 'SALA DE ELABORACION';
        if (preg_match('/ACCESORIOS|BALANCEADOS|ARMERÍA|INDUMENTARIA|JUGUETERÍA|LIBRERÍA|REPUESTOS|REGIONALES|FORRAJERÍA|ARTÍCULOS/', $rubro)) return 'COMERCIO';
        if (preg_match('/ALQUILER|BANCO|CAJERO|CARPINTERÍA|ADMINISTRATIVO|DISEÑO|EMPRESA|FOTOGRAFÍA|PRÉSTAMOS|GRÁFICA|LAVADERO|MOVIMIENTO DE SUELO|MUTUAL|OFICINA|SEGUROS|EVENTOS|ENCOMIENDAS/', $rubro)) return 'SERVICIOS';
        if (preg_match('/DESTILERÍA|FÁBRICA DE BLOQUES|FÁBRICA DE PREMOLDEADOS/', $rubro)) return 'AGRO / PRODUCCION';

        return 'OTROS';
    }

    public function down(): void
    {
        // No se eliminan rubros porque podrían quedar asociados a comercios.
    }
};
