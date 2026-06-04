<?php

namespace App\Support;

trait HandlesEstados
{
    
    protected function estadoBaseNormalize(?string $raw): string
    {
        $s = trim(mb_strtolower((string)$raw));

        // Compuestos tipo "021 - Cambio ..." o "032 - ..."
        if (str_starts_with($s, '021')) return '021';
        if (str_starts_with($s, '032')) return '032';
        if (str_starts_with($s, '040')) return '040';

        return match ($s) {
            // canónicos o alias → base
            'entramite','en tramite','en trámite','en_tramite','en-tramite','vigente','alta' => '021',
            'irregular'         => '032',
            '040','040/25'      => '040',

            'baja'              => 'baja',
            'baja de oficio','baja_oficio','baja-oficio' => 'baja_oficio',
            'sin_efecto','expediente sin efecto','exp_sin_efecto','exp-sin-efecto' => 'exp_sin_efecto',

            default => '021',
        };
    }

    protected function estadoBaseNormalizeFromRaw(?string $raw): string {
        $s = trim(mb_strtolower((string)$raw));

        if (preg_match('/^\s*0?21\b/', $s)) return '021';
        if (preg_match('/^\s*0?32\b/', $s)) return '032';
        if (preg_match('/^\s*0?40\b/', $s)) return '040'; // <-- FALTABA

        if (in_array($s, ['entramite','en tramite','en trámite','en_tramite','en-tramite','alta','vigente'], true)) return '021';
        if ($s==='irregular') return '032';

        if ($s==='baja') return 'baja';
        if (str_contains($s, 'baja de oficio') || str_contains($s,'oficio')) return 'baja_oficio';
        if (str_contains($s, 'expediente sin efecto') || $s==='sin_efecto' || $s==='exp_sin_efecto') return 'exp_sin_efecto';

        return '021';
    }


    protected function mapBaseToCanon(string $base): string
    {
        return match ($base) {
            '021'          => 'entramite',
            '032'          => 'irregular',
            '040'          => '040',
            'baja'         => 'baja',
            'baja_oficio'  => 'baja_oficio',
            'exp_sin_efecto' => 'sin_efecto',
            default        => 'entramite',
        };
    }

    protected function cambiosOptionsByBase(string $estadoBase): array
    {
        return match ($estadoBase) {
            '021' => [
                '' => 'Ninguno',
                'cambio_domicilio' => 'Cambio de Domicilio',
                'adicion_anexo'    => 'Adición de Rubro Anexo',
                'cambio_razon'     => 'Cambio de Razón Social',
                'resolucion_482'   => 'Resolución 482/22',
                'permiso_habilitante' => 'Permiso Habilitante',
                'sala_de_elaboracion' => 'Sala de Elaboración',
                'cambio_fantasia'  => 'Cambio de Nombre de Fantasia',

            ],
            '032' => [
                '' => 'Ninguno',
                'cambio_rubro'     => 'Cambio de Rubro',
                'adicion_anexo'    => 'Adecion de Rubro Anexo',
                'cambio_fantasia'  => 'Cambio de Nombre de Fantasia',
                'baja_alojamiento' => 'Baja de Unidad de Alojamiento',
                'cambio_razon'     => 'Cambio de Razon Social',
                'permiso_habilitante' => 'Permiso Habilitante',
                'sala_de_elaboracion' => 'Sala de Elaboración',
            ],
            default => [], 
        };
    }

    protected function cambiosOptions(string $estadoBase): array {
        return match ($estadoBase) {
            '021' => [
                '' => 'Ninguno',
                'cambio_domicilio' => 'Cambio de Domicilio',
                'adicion_anexo'    => 'Adición de Rubro Anexo',
                'cambio_razon'     => 'Cambio de Razón Social',
                'resolucion_482'   => 'Resolución 482/22',
                'permiso_habilitante' => 'Permiso Habilitante',
                'sala_de_elaboracion' => 'Sala de Elaboración',
                'cambio_fantasia'  => 'Cambio de Nombre de Fantasia',
            ],
            '032' => [
                '' => 'Ninguno',
                'cambio_rubro'     => 'Cambio de Rubro',
                'adicion_anexo'    => 'Adecion de Rubro Anexo',
                'cambio_fantasia'  => 'Cambio de Nombre de Fantasia',
                'baja_alojamiento' => 'Baja de Unidad de Alojamiento',
                'cambio_razon'     => 'Cambio de Razon Social',
                'permiso_habilitante' => 'Permiso Habilitante',
                'sala_de_elaboracion' => 'Sala de Elaboración',
            ],
            default => ['' => 'Ninguno'],
        };
    }

    protected function buildEstadoLabel(string $estadoBase, ?string $cambioKey = ''): string
    {
        $cambioKey = (string)($cambioKey ?? '');
        if ($estadoBase === '021' || $estadoBase === '032') {
            $opts = $this->cambiosOptionsByBase($estadoBase);
            $suffix = ($cambioKey && isset($opts[$cambioKey]) && $opts[$cambioKey] !== 'Ninguno')
                ? (' - ' . $opts[$cambioKey]) : '';
            return $estadoBase . $suffix;
        }

        return match ($estadoBase) {
            '040'           => '040/25', // o solo '040' si preferís
            'baja'          => 'Baja',
            'baja_oficio'   => 'Baja de Oficio',
            'exp_sin_efecto'=> 'Expediente sin Efecto',
            default         => strtoupper($estadoBase),
        };
    }

    protected function parseCambioDesdeEstado(string $estadoRaw): array
    {
        $raw = trim($estadoRaw);
        $sl  = mb_strtolower($raw);

        // Base
        if (str_starts_with($sl, '021')) $base = '021';
        elseif (str_starts_with($sl, '032')) $base = '032';
        elseif (str_starts_with($sl, '040')) $base = '040';
        elseif (in_array($sl, ['entramite','en tramite','en trámite','en_tramite','en-tramite','vigente','alta'])) $base = '021';
        elseif ($sl === 'irregular') $base = '032';
        elseif (in_array($sl, ['baja','baja de oficio','baja_oficio','baja-oficio','expediente sin efecto','sin_efecto','exp_sin_efecto'])) {
            return ['base' => $this->estadoBaseNormalize($sl), 'cambio_key' => null];
        } else {
            $base = '021';
        }

        // 040 no tiene cambio
        if ($base === '040') {
            return ['base' => '040', 'cambio_key' => null];
        }

        // Extraer etiqueta a la derecha del guion, si existe
        $label = '';
        if (str_contains($raw, '-')) {
            $label = trim(explode('-', $raw, 2)[1] ?? '');
        }
        if ($label === '') return ['base' => $base, 'cambio_key' => null];

        $opts = $this->cambiosOptionsByBase($base);
        $buscado = mb_strtolower($label);
        foreach ($opts as $key => $txt) {
            if (mb_strtolower($txt) === $buscado) {
                return ['base' => $base, 'cambio_key' => $key];
            }
        }
        return ['base' => $base, 'cambio_key' => null];
    }

    protected function inferCambioKeyFromEstado(?string $estadoRaw, string $base): string {
        $label = trim((string)($estadoRaw ?? ''));
        if ($label === '') return '';
        if (str_contains($label,'-')) {
            return $this->parseCambioDesdeEstado($label)['cambio_key'] ?? '';
        }
        $labelLower = mb_strtolower($label);
        foreach ($this->cambiosOptions($base) as $k => $txt) {
            if ($k==='') continue;
            $txtLower = mb_strtolower($txt);
            if ($txtLower === $labelLower || str_replace(' ','',$txtLower)===str_replace(' ','',$labelLower)) {
                return $k;
            }
        }
        return '';
    }
}
