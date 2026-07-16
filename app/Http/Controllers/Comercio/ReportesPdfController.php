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
        $filters = $this->filters($request);
        $items = $reportes->items($filters);
        $filterLabels = $reportes->filterLabels($filters);

        return Pdf::loadView('comercio.reportes-pdf', ['items' => $items, 'filters' => $filterLabels])
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
        ];
    }
}
