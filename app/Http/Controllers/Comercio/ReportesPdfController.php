<?php

namespace App\Http\Controllers\Comercio;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportesPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $rubroId = $request->integer('rubro_id') ?: null;
        $estado = $request->filled('estado') ? (string) $request->query('estado') : null;
        $rubroGeneral = $request->filled('rubroGeneral') ? (string) $request->query('rubroGeneral') : null;
        $desde = $request->filled('desde') ? (string) $request->query('desde') : null;
        $hasta = $request->filled('hasta') ? (string) $request->query('hasta') : null;
        $proximosVtos = max(1, min((int) $request->integer('proximos_vtos', 30), 365));
        $soloClausurados = $request->boolean('solo_clausurados');

        $rubrosTieneGeneral = Schema::hasTable('rubros') && Schema::hasColumn('rubros', 'rubro_general');

        $query = DB::table('ubicaciones as u')
            ->leftJoin('rubros as r', 'r.id', '=', 'u.rubro_id')
            ->when($rubroId, fn ($q) => $q->where('u.rubro_id', $rubroId))
            ->when($estado, fn ($q) => $q->where('u.estado', $estado))
            ->when($soloClausurados, fn ($q) => $q->where('u.situacion', 'clausurado'))
            ->when($rubroGeneral && $rubrosTieneGeneral, fn ($q) => $q->where('r.rubro_general', $rubroGeneral))
            ->orderBy('u.nombre_comercial')
            ->orderBy('u.razon_social')
            ->select([
                'u.nombre_comercial',
                'u.razon_social',
                'u.apellido',
                'u.nombres',
                'u.telefono',
                'u.fecha_vto',
                'u.domicilio_comercio',
                'r.subrubro',
            ]);

        $items = $query->limit(1000)->get()->map(function ($r) {
            $fantasia = $this->value($r->nombre_comercial ?? null);
            $titular = $this->value($r->razon_social ?? null);

            if ($titular === '-') {
                $titular = trim(($r->apellido ?? '').' '.($r->nombres ?? '')) ?: '-';
            }

            return [
                'fantasia' => $fantasia,
                'titular' => $titular,
                'telefonos' => $this->value($r->telefono ?? null),
                'vto' => !empty($r->fecha_vto) ? Carbon::parse($r->fecha_vto)->format('Y-m-d') : '-',
                'direccion' => $this->value($r->domicilio_comercio ?? null),
                'subrubro' => $this->value($r->subrubro ?? null),
            ];
        });

        $lines = [
            'Reporte de Habilitaciones Comerciales',
            'Generado el '.now()->format('d/m/Y H:i'),
            'Subrubro: '.($rubroId ?: 'Todos').' | Estado: '.($estado ?: 'Todos').' | Desde: '.($desde ?: '-').' | Hasta: '.($hasta ?: '-').' | Prox. vto: '.$proximosVtos.' dias | Clausurados: '.($soloClausurados ? 'Si' : 'No'),
            str_repeat('-', 120),
        ];

        if ($items->isEmpty()) {
            $lines[] = 'Sin datos para los filtros seleccionados.';
        } else {
            foreach ($items as $item) {
                $lines[] = $this->clip($item['fantasia'], 28).' | '.$this->clip($item['titular'], 28).' | '.$this->clip($item['telefonos'], 16).' | '.$this->clip($item['vto'], 10);
                $lines[] = '  '.$this->clip($item['direccion'], 60).' | '.$this->clip($item['subrubro'], 45);
                $lines[] = '';
            }
        }

        $pdf = $this->buildPdf($lines);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_habilitaciones_'.now()->format('Ymd_His').'.pdf"',
        ]);
    }

    private function value(?string $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : '-';
    }

    private function clip(string $value, int $length): string
    {
        $value = preg_replace('/\s+/', ' ', $this->ascii($value));

        return strlen($value) > $length ? substr($value, 0, $length - 3).'...' : $value;
    }

    private function ascii(string $value): string
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $converted !== false ? $converted : $value;
    }

    private function buildPdf(array $lines): string
    {
        $pages = array_chunk($lines, 42);
        $objects = [];
        $pagesObjectNumber = 2;
        $fontObjectNumber = 3;
        $pageObjectNumbers = [];

        $objects[1] = '<< /Type /Catalog /Pages '.$pagesObjectNumber.' 0 R >>';
        $objects[$fontObjectNumber] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $nextObject = 4;
        foreach ($pages as $pageLines) {
            $pageObject = $nextObject++;
            $contentObject = $nextObject++;
            $pageObjectNumbers[] = $pageObject;

            $stream = "BT\n/F1 10 Tf\n50 790 Td\n";
            foreach ($pageLines as $index => $line) {
                if ($index > 0) {
                    $stream .= "0 -16 Td\n";
                }
                $stream .= '('.$this->pdfEscape($this->ascii($line)).") Tj\n";
            }
            $stream .= "ET\n";

            $objects[$pageObject] = '<< /Type /Page /Parent '.$pagesObjectNumber.' 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 '.$fontObjectNumber.' 0 R >> >> /Contents '.$contentObject.' 0 R >>';
            $objects[$contentObject] = "<< /Length ".strlen($stream)." >>\nstream\n".$stream."endstream";
        }

        $objects[$pagesObjectNumber] = '<< /Type /Pages /Kids ['.implode(' ', array_map(fn ($n) => $n.' 0 R', $pageObjectNumbers)).'] /Count '.count($pageObjectNumbers).' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";

        return $pdf;
    }

    private function pdfEscape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
