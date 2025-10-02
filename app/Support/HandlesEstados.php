<?php

namespace App\Support;

trait HandlesEstados
{
    protected function estadoBaseNormalize(?string $raw): string {
        $e = trim(mb_strtolower((string)$raw));
        return match ($e) {
            '021','en tramite','en trámite','en_tramite','entramite','en-tramite','alta','vigente' => '021',
            '032','irregular' => '032',
            'baja' => 'baja',
            'baja de oficio','baja_oficio','baja-de-oficio' => 'baja_oficio',
            'expediente sin efecto','sin_efecto','exp_sin_efecto' => 'exp_sin_efecto',
            default => '021',
        };
    }

    protected function estadoBaseNormalizeFromRaw(?string $raw): string {
        $s = trim(mb_strtolower((string)$raw));
        if (preg_match('/^\s*0?21\b/', $s)) return '021';
        if (preg_match('/^\s*0?32\b/', $s)) return '032';
        if (in_array($s, ['entramite','en tramite','en trámite','en_tramite','en-tramite','alta','vigente'], true)) return '021';
        if ($s==='irregular') return '032';
        if ($s==='baja') return 'baja';
        if (str_contains($s, 'baja de oficio') || str_contains($s,'oficio')) return 'baja_oficio';
        if (str_contains($s, 'expediente sin efecto') || $s==='sin_efecto' || $s==='exp_sin_efecto') return 'exp_sin_efecto';
        return '021';
    }

    protected function mapBaseToCanon(string $base): string {
        return match ($base) {
            '021' => 'entramite',
            '032' => 'irregular',
            'baja' => 'baja',
            'baja_oficio' => 'baja_oficio',
            'exp_sin_efecto' => 'sin_efecto',
            default => 'entramite',
        };
    }

    protected function cambiosOptions(string $estadoBase): array {
        return match ($estadoBase) {
            '021' => [
                '' => 'Ninguno',
                'cambio_domicilio' => 'Cambio de Domicilio',
                'adicion_anexo'    => 'Adición de Rubro Anexo',
                'cambio_razon'     => 'Cambio de Razón Social',
            ],
            '032' => [
                '' => 'Ninguno',
                'cambio_rubro'     => 'Cambio de Rubro',
                'adicion_anexo'    => 'Adecion de Rubro Anexo',
                'cambio_fantasia'  => 'Cambio de Nombre de Fantasia',
                'baja_alojamiento' => 'Baja de Unidad de Alojamiento',
                'cambio_razon'     => 'Cambio de Razon Social',
            ],
            default => ['' => 'Ninguno'],
        };
    }

    protected function buildEstadoLabel(string $estadoBase, ?string $cambioKey): string {
        $opts = $this->cambiosOptions($estadoBase);
        $labelCambio = $opts[$cambioKey ?? ''] ?? 'Ninguno';
        return match ($estadoBase) {
            '021' => $labelCambio !== 'Ninguno' ? "021- {$labelCambio}" : '021',
            '032' => $labelCambio !== 'Ninguno' ? "032- {$labelCambio}" : '032',
            'baja' => 'Baja',
            'baja_oficio' => 'Baja de Oficio',
            'exp_sin_efecto' => 'Expediente sin Efecto',
            default => strtoupper($estadoBase),
        };
    }

    protected function parseCambioDesdeEstado(?string $estadoCrudo): array {
        $estadoCrudo = (string)$estadoCrudo;
        $base = $this->estadoBaseNormalizeFromRaw($estadoCrudo);
        $key = '';
        if (str_contains($estadoCrudo, '-')) {
            [, $labelParte] = array_map('trim', explode('-', $estadoCrudo, 2));
            $labelLower = mb_strtolower($labelParte);
            foreach ($this->cambiosOptions($base) as $k => $txt) {
                if ($k === '') continue;
                $txtLower = mb_strtolower($txt);
                if ($txtLower === $labelLower || str_replace(' ','',$txtLower)===str_replace(' ','',$labelLower)) {
                    $key = $k; break;
                }
            }
        }
        return ['base'=>$base,'cambio_key'=>$key];
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
