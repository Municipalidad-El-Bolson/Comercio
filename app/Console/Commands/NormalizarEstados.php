<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Ubicacion;

class NormalizarEstados extends Command
{
    protected $signature = 'ubicaciones:normalizar-estados {--dry : Muestra cambios sin aplicarlos}';
    protected $description = 'Corrige estados canónicos y base de ubicaciones (vigente → 021, etc.)';

    public function handle(): int
    {
        $dry = (bool)$this->option('dry');
        $count = 0;

        $this->info("Normalizando estados de ubicaciones...");
        $this->line("Modo: " . ($dry ? 'simulación (dry-run)' : 'aplicando cambios'));

        Ubicacion::chunkById(500, function ($chunk) use ($dry, &$count) {
            foreach ($chunk as $u) {
                $orig_estado = $u->estado;
                $orig_base   = $u->estado_base;

                $canonico = $this->normalizarCanonico($orig_estado);
                $base     = $this->estadoBaseDesdeCanonico($canonico);

                // Si hay algo para cambiar
                if ($canonico !== $orig_estado || $base !== $orig_base) {
                    $count++;
                    if ($dry) {
                        $this->line("→ [{$u->id}] {$orig_estado}/{$orig_base} → {$canonico}/{$base}");
                    } else {
                        $u->updateQuietly([
                            'estado'       => $canonico,
                            'estado_base'  => $base,
                        ]);
                    }
                }
            }
        });

        $this->info(($dry ? 'Simulación completada.' : 'Corrección aplicada.') . " Total: {$count} registros.");
        return self::SUCCESS;
    }

    /** Devuelve el estado canónico corregido */
    private function normalizarCanonico(?string $estado): string
    {
        $s = mb_strtolower(trim((string)$estado));

        return match (true) {
            $s === '', $s === 'alta', $s === 'vigente', $s === '021', str_contains($s, 'tramite') => 'entramite',
            $s === 'irregular', $s === '032'                                                => 'irregular',
            $s === '040', $s === 'previa', $s === 'pre'                                    => '040',
            $s === 'baja'                                                                   => 'baja',
            $s === 'baja de oficio', $s === 'baja_oficio'                                  => 'baja_oficio',
            str_contains($s, 'sin efecto'), str_contains($s, 'sin_efecto')                 => 'exp_sin_efecto',
            default                                                                         => $s ?: 'entramite',
        };
    }

    /** Devuelve el código base asociado */
    private function estadoBaseDesdeCanonico(string $canonico): string
    {
        return match ($canonico) {
            'entramite'     => '021',
            'irregular'     => '032',
            '040'           => '040',
            'baja'          => 'baja',
            'baja_oficio'   => 'baja_oficio',
            'exp_sin_efecto'=> 'exp_sin_efecto',
            default         => '021',
        };
    }
}
