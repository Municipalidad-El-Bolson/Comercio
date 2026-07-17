<?php

namespace App\Http\Controllers\Comercio;

use App\Http\Controllers\Controller;
use App\Support\ReportesComercioData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportesPdfController extends Controller
{
    public function __invoke(Request $request, ReportesComercioData $reportes)
    {
        @ini_set('memory_limit', '256M');
        $filters = $this->filters($request);
        $total = $reportes->query($filters)->count();
        $pdfLimit = 150;
        $items = $reportes->items($filters, $pdfLimit);
        $truncated = $total > $pdfLimit;
        $filterLabels = $reportes->filterLabels($filters);

        return Pdf::loadView('comercio.reportes-pdf', compact('items', 'filterLabels', 'total', 'truncated'))
            ->setPaper('a4', 'landscape')
            ->download('reporte_habilitaciones_'.now()->format('Ymd_His').'.pdf');
    }

    public static function filters(Request $request): array
    {
        return [
            'rubro_id' => $request->integer('rubro_id') ?: null,
            'rubro_general' => $request->filled('rubroGeneral') ? (string) $request->query('rubroGeneral') : null,
            'estado' => $request->filled('estado') ? (string) $request->query('estado') : null,
            'desde' => $request->filled('desde') ? (string) $request->query('desde') : null,
            'hasta' => $request->filled('hasta') ? (string) $request->query('hasta') : null,
            'proximos_vtos' => $request->filled('proximos_vtos')
                ? max(1, min((int) $request->integer('proximos_vtos'), 365)) : null,
            'solo_clausurados' => $request->boolean('solo_clausurados'),
            'solo_baja_temporaria' => $request->boolean('solo_baja_temporaria'),
        ];
    }
}
