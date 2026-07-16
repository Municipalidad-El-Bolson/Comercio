<?php

namespace App\Http\Controllers\Comercio;

use App\Http\Controllers\Controller;
use App\Support\ReportesComercioData;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportesExcelController extends Controller
{
    public function __invoke(Request $request, ReportesComercioData $reportes): StreamedResponse
    {
        $filters = ReportesPdfController::filters($request);
        $items = $reportes->items($filters);
        $labels = $reportes->filterLabels($filters);

        return response()->streamDownload(function () use ($items, $labels) {
            echo '<?xml version="1.0" encoding="UTF-8"?><?mso-application progid="Excel.Sheet"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            echo '<Styles>';
            echo '<Style ss:ID="Title"><Font ss:Bold="1" ss:Size="16" ss:Color="#FFFFFF"/><Interior ss:Color="#173B63" ss:Pattern="Solid"/></Style>';
            echo '<Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#2F6597" ss:Pattern="Solid"/><Alignment ss:Vertical="Center" ss:WrapText="1"/></Style>';
            echo '<Style ss:ID="Text"><Alignment ss:Vertical="Top" ss:WrapText="1"/></Style>';
            echo '<Style ss:ID="Integer"><Alignment ss:Horizontal="Right"/><NumberFormat ss:Format="0"/></Style>';
            echo '<Style ss:ID="Date"><NumberFormat ss:Format="dd/mm/yyyy"/></Style>';
            echo '</Styles><Worksheet ss:Name="Habilitaciones"><Table>';
            foreach ([130, 180, 85, 330, 125, 90, 180, 110, 80, 80, 95, 105, 105] as $width) {
                echo '<Column ss:Width="'.$width.'"/>';
            }
            echo '<Row ss:Height="26"><Cell ss:MergeAcross="12" ss:StyleID="Title"><Data ss:Type="String">Reporte de habilitaciones comerciales</Data></Cell></Row>';
            echo '<Row><Cell ss:MergeAcross="12" ss:StyleID="Text"><Data ss:Type="String">'.$this->xml($this->labelsText($labels)).'</Data></Cell></Row>';
            echo '<Row ss:StyleID="Header">';
            foreach (['Nombre comercial', 'Titular completo', 'HC', 'Rubro principal', 'Trámite', 'Fecha asociada', 'Dirección', 'Teléfono/s', 'Unidades', 'Plazas', 'Situación', 'Suspensión desde', 'Suspensión hasta'] as $heading) {
                echo $this->stringCell($heading);
            }
            echo '</Row>';

            foreach ($items as $item) {
                echo '<Row>';
                foreach (['nombre_comercial', 'titular', 'hc', 'rubros', 'tramite'] as $key) echo $this->stringCell($item[$key]);
                echo $this->dateCell($item['fecha_asociada_raw']);
                foreach (['direccion', 'telefonos'] as $key) echo $this->stringCell($item[$key]);
                echo $this->integerCell($item['unidades']);
                echo $this->integerCell($item['plazas']);
                echo $this->stringCell($item['situacion']);
                echo $this->dateCell($item['suspension_desde_raw']);
                echo $this->dateCell($item['suspension_hasta_raw']);
                echo '</Row>';
            }

            echo '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>3</SplitHorizontal><TopRowBottomPane>3</TopRowBottomPane><AutoFilter x:Range="R3C1:R'.($items->count() + 3).'C13" xmlns:x="urn:schemas-microsoft-com:office:excel"/></WorksheetOptions></Worksheet></Workbook>';
        }, 'reporte_habilitaciones_'.now()->format('Ymd_His').'.xls', [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    private function stringCell($value): string
    {
        return '<Cell ss:StyleID="Text"><Data ss:Type="String">'.$this->xml((string) ($value ?? '-')).'</Data></Cell>';
    }

    private function integerCell($value): string
    {
        return $value === null
            ? $this->stringCell('-')
            : '<Cell ss:StyleID="Integer"><Data ss:Type="Number">'.(int) $value.'</Data></Cell>';
    }

    private function dateCell(?string $value): string
    {
        return $value
            ? '<Cell ss:StyleID="Date"><Data ss:Type="DateTime">'.$value.'T00:00:00.000</Data></Cell>'
            : $this->stringCell('-');
    }

    private function labelsText(array $labels): string
    {
        return collect($labels)->map(fn ($value, $label) => "{$label}: {$value}")->implode(' | ');
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
