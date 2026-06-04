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

        $meta = [
            'generado' => now()->format('d/m/Y H:i'),
            'rubro' => $rubroGeneral ?: ($rubroId ?: 'Todos'),
            'estado' => $estado ?: 'Todos',
            'desde' => $desde ?: '-',
            'hasta' => $hasta ?: '-',
            'proximos' => $proximosVtos.' dias',
            'clausurados' => $soloClausurados ? 'Si' : 'No',
        ];

        $pdf = $this->buildPdf($items->all(), $meta);

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

    private function buildPdf(array $items, array $meta): string
    {
        $pages = empty($items) ? [[]] : array_chunk($items, 18);
        $objects = [];
        $pagesObjectNumber = 2;
        $fontObjectNumber = 3;
        $boldFontObjectNumber = 4;
        $pageObjectNumbers = [];

        $objects[1] = '<< /Type /Catalog /Pages '.$pagesObjectNumber.' 0 R >>';
        $objects[$fontObjectNumber] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[$boldFontObjectNumber] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        $nextObject = 5;
        $totalPages = count($pages);

        foreach ($pages as $pageIndex => $pageItems) {
            $pageObject = $nextObject++;
            $contentObject = $nextObject++;
            $pageObjectNumbers[] = $pageObject;

            $stream = '';
            $stream .= $this->fillRect(40, 785, 515, 38, '0.10 0.23 0.39');
            $stream .= $this->text('Reporte de Habilitaciones Comerciales', 52, 807, 16, true, '1 1 1');
            $stream .= $this->text('Municipalidad de El Bolson', 52, 792, 8, false, '0.86 0.92 1');
            $stream .= $this->text('Generado: '.$meta['generado'], 430, 807, 8, false, '1 1 1');

            $stream .= $this->strokeRect(40, 720, 515, 52, '0.75 0.80 0.86');
            $stream .= $this->text('Filtros aplicados', 52, 755, 9, true, '0.10 0.16 0.24');
            $stream .= $this->text('Rubro: '.$this->clip($meta['rubro'], 58), 52, 740, 8);
            $stream .= $this->text('Estado: '.$meta['estado'].'   Desde: '.$meta['desde'].'   Hasta: '.$meta['hasta'], 52, 728, 8);
            $stream .= $this->text('Proximos vencimientos: '.$meta['proximos'].'   Solo clausurados: '.$meta['clausurados'], 310, 728, 8);

            $columns = [
                ['label' => 'Nombre comercial', 'key' => 'fantasia', 'x' => 40, 'w' => 92],
                ['label' => 'Titular', 'key' => 'titular', 'x' => 132, 'w' => 96],
                ['label' => 'Telefono', 'key' => 'telefonos', 'x' => 228, 'w' => 62],
                ['label' => 'Vto.', 'key' => 'vto', 'x' => 290, 'w' => 52],
                ['label' => 'Subrubro', 'key' => 'subrubro', 'x' => 342, 'w' => 92],
                ['label' => 'Direccion', 'key' => 'direccion', 'x' => 434, 'w' => 121],
            ];

            $y = 684;
            $stream .= $this->fillRect(40, $y, 515, 22, '0.90 0.93 0.96');
            $stream .= $this->strokeRect(40, $y, 515, 22, '0.72 0.78 0.84');

            foreach ($columns as $column) {
                $stream .= $this->text($column['label'], $column['x'] + 4, $y + 8, 7.5, true, '0.14 0.20 0.29');
            }

            if (empty($pageItems)) {
                $stream .= $this->strokeRect(40, 630, 515, 38, '0.82 0.86 0.90');
                $stream .= $this->text('Sin datos para los filtros seleccionados.', 205, 647, 9, false, '0.36 0.42 0.50');
            }

            $rowY = 656;
            foreach ($pageItems as $rowIndex => $item) {
                $fill = $rowIndex % 2 === 0 ? '1 1 1' : '0.97 0.98 0.99';
                $stream .= $this->fillRect(40, $rowY, 515, 28, $fill);
                $stream .= $this->strokeRect(40, $rowY, 515, 28, '0.86 0.89 0.92');

                foreach ($columns as $column) {
                    $max = match ($column['key']) {
                        'fantasia' => 18,
                        'titular' => 20,
                        'telefonos' => 12,
                        'vto' => 10,
                        'subrubro' => 18,
                        default => 25,
                    };
                    $stream .= $this->text($this->clip($item[$column['key']] ?? '-', $max), $column['x'] + 4, $rowY + 17, 7);
                }

                $rowY -= 28;
            }

            $stream .= $this->line(40, 64, 555, 64, '0.75 0.80 0.86');
            $stream .= $this->text('Pagina '.($pageIndex + 1).' de '.$totalPages, 490, 48, 8, false, '0.36 0.42 0.50');
            $stream .= $this->text('Reporte generado automaticamente', 40, 48, 8, false, '0.36 0.42 0.50');

            $objects[$pageObject] = '<< /Type /Page /Parent '.$pagesObjectNumber.' 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 '.$fontObjectNumber.' 0 R /F2 '.$boldFontObjectNumber.' 0 R >> >> /Contents '.$contentObject.' 0 R >>';
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

    private function text(string $value, float $x, float $y, float $size = 10, bool $bold = false, string $color = '0.10 0.10 0.10'): string
    {
        $font = $bold ? 'F2' : 'F1';

        return "{$color} rg\nBT\n/{$font} {$size} Tf\n{$x} {$y} Td\n(".$this->pdfEscape($this->ascii($value)).") Tj\nET\n";
    }

    private function fillRect(float $x, float $y, float $w, float $h, string $color): string
    {
        return "{$color} rg\n{$x} {$y} {$w} {$h} re f\n";
    }

    private function strokeRect(float $x, float $y, float $w, float $h, string $color): string
    {
        return "{$color} RG\n{$x} {$y} {$w} {$h} re S\n";
    }

    private function line(float $x1, float $y1, float $x2, float $y2, string $color): string
    {
        return "{$color} RG\n{$x1} {$y1} m\n{$x2} {$y2} l\nS\n";
    }
}
