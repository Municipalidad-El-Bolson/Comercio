<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        $dbName = DB::getDatabaseName();

        // Traer todos los índices de la tabla
        $rows = DB::table('INFORMATION_SCHEMA.STATISTICS')
            ->select('INDEX_NAME', 'NON_UNIQUE', 'SEQ_IN_INDEX', 'COLUMN_NAME')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'ubicaciones')
            ->orderBy('INDEX_NAME')
            ->orderBy('SEQ_IN_INDEX')
            ->get()
            ->groupBy('INDEX_NAME');

        // Helper: devuelve true si el índice es ÚNICO y sus columnas == $cols (en orden)
        $isUniqueWithColumns = function ($parts, array $cols): bool {
            if (!$parts || count($parts) !== count($cols)) return false;
            // NON_UNIQUE = 0 => índice único
            if ((int)($parts->first()->NON_UNIQUE ?? 1) !== 0) return false;
            $colsInIdx = $parts->pluck('COLUMN_NAME')->values()->all();
            return $colsInIdx === array_values($cols);
        };

        // Helper: baja un índice por nombre
        $dropIndex = function (string $indexName) {
            DB::statement("ALTER TABLE `ubicaciones` DROP INDEX `{$indexName}`");
        };

        // Buscar y bajar cualquier UNIQUE(dni_cuit)
        foreach ($rows as $name => $parts) {
            if ($isUniqueWithColumns($parts, ['dni_cuit'])) {
                $dropIndex($name);
            }
        }

        // Buscar y bajar cualquier UNIQUE(razon_social)
        foreach ($rows as $name => $parts) {
            if ($isUniqueWithColumns($parts, ['razon_social'])) {
                $dropIndex($name);
            }
        }

        // Buscar y bajar cualquier UNIQUE(apellido, nombres)
        foreach ($rows as $name => $parts) {
            if ($isUniqueWithColumns($parts, ['apellido','nombres'])) {
                $dropIndex($name);
            }
        }

        // Reconsultar índices después de dropear (estado actualizado)
        $rows = DB::table('INFORMATION_SCHEMA.STATISTICS')
            ->select('INDEX_NAME', 'NON_UNIQUE', 'SEQ_IN_INDEX', 'COLUMN_NAME')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'ubicaciones')
            ->orderBy('INDEX_NAME')
            ->orderBy('SEQ_IN_INDEX')
            ->get()
            ->groupBy('INDEX_NAME');

        // Helper: asegura que exista un índice NO ÚNICO exacto sobre $cols
        $ensureIndex = function (string $name, array $cols) use ($rows) {
            foreach ($rows as $iName => $parts) {
                $colsInIdx = $parts->pluck('COLUMN_NAME')->values()->all();
                if ($colsInIdx === array_values($cols)) {
                    // ya hay índice (único o no); si era único ya lo bajamos arriba,
                    // si queda uno no-único igual sirve
                    return;
                }
            }
            $colsSql = implode(',', array_map(fn($c) => "`{$c}`", $cols));
            DB::statement("ALTER TABLE `ubicaciones` ADD INDEX `{$name}` ({$colsSql})");
        };

        // Crear índices normales si faltan
        $ensureIndex('ubicaciones_dni_cuit_index', ['dni_cuit']);
        $ensureIndex('ubicaciones_razon_social_index', ['razon_social']);
        $ensureIndex('ubicaciones_apellido_nombres_index', ['apellido','nombres']);

        // OPCIONAL: evitar duplicar EXACTAMENTE el mismo local (mismo DNI + misma dirección)
        // Descomentar si querés esta protección:
        // DB::statement("ALTER TABLE `ubicaciones` ADD UNIQUE `uniq_dni_dir` (`dni_cuit`,`domicilio_comercio`)");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Bajar los índices normales (si existen)
        foreach (['ubicaciones_dni_cuit_index','ubicaciones_razon_social_index','ubicaciones_apellido_nombres_index'] as $idx) {
            try { DB::statement("ALTER TABLE `ubicaciones` DROP INDEX `{$idx}`"); } catch (\Throwable $e) {}
        }

        // Quitar el UNIQUE opcional si lo activaste
        try { DB::statement("ALTER TABLE `ubicaciones` DROP INDEX `uniq_dni_dir`"); } catch (\Throwable $e) {}

        // (No restauramos los UNIQUE anteriores porque sus nombres/estado previo pueden ser desconocidos)
    }
};
