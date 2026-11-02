<?php

declare(strict_types=1);

namespace App\Modules\Item\Services;

use App\Modules\Category\Models\Category;
use App\Modules\Currency\Repositories\Contracts\CurrencyRepositoryInterface;
use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\Tax\Models\Tax;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemTemplateService
{
    public function __construct(
        private readonly CurrencyRepositoryInterface $currencyRepository,
    ) {}

    /**
     * Cabeceras de la hoja principal de artículos.
     *
     * @var array<int, array{key: string, label: string, width: int}>
     */
    public const COLUMNS = [
        ['key' => 'sku', 'label' => 'SKU (*)', 'width' => 18],
        ['key' => 'nombre', 'label' => 'Nombre (*)', 'width' => 32],
        ['key' => 'codigo_barras', 'label' => 'Código de barras', 'width' => 20],
        ['key' => 'precio', 'label' => 'Precio', 'width' => 14],
        ['key' => 'moneda', 'label' => 'Moneda', 'width' => 12],
        ['key' => 'lista_precio', 'label' => 'Lista de precio', 'width' => 22],
        ['key' => 'costo_estandar', 'label' => 'Costo estándar', 'width' => 16],
        ['key' => 'precio_minimo', 'label' => 'Precio mínimo', 'width' => 16],
        ['key' => 'tipo', 'label' => 'Tipo', 'width' => 18],
        ['key' => 'categoria', 'label' => 'Categoría', 'width' => 22],
        ['key' => 'unidad_base', 'label' => 'Unidad base', 'width' => 16],
        ['key' => 'impuesto_venta', 'label' => 'Impuesto venta (*)', 'width' => 22],
        ['key' => 'impuesto_compra', 'label' => 'Impuesto compra (*)', 'width' => 22],
        ['key' => 'descripcion', 'label' => 'Descripción', 'width' => 35],
        ['key' => 'notas', 'label' => 'Notas', 'width' => 30],
    ];

    public function download(string $companyId, bool $includeCurrent = false): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('ERP')
            ->setTitle($includeCurrent ? 'Catálogo de Artículos' : 'Plantilla de Importación de Artículos');

        $this->buildItemsSheet($spreadsheet, $companyId, $includeCurrent);
        $this->buildReferenceSheet($spreadsheet, $companyId);

        // Seleccionar la primera hoja
        $spreadsheet->setActiveSheetIndex(0);

        $filename = $includeCurrent
            ? 'catalogo_articulos_'.date('Ymd_His').'.xlsx'
            : 'plantilla_articulos.xlsx';

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function buildItemsSheet(Spreadsheet $spreadsheet, string $companyId, bool $includeCurrent): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Artículos');

        // Estilo de encabezados
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'], // Tailwind blue-600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '1D4ED8'],
                ],
            ],
        ];

        // Escribir cabeceras
        foreach (self::COLUMNS as $colIndex => $col) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue("{$colLetter}1", $col['label']);
            $sheet->getColumnDimension($colLetter)->setWidth($col['width']);
        }

        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count(self::COLUMNS));
        $sheet->getStyle("A1:{$lastColLetter}1")->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->freezePane('A2');

        $rowNum = 2;

        if ($includeCurrent) {
            $items = Item::query()
                ->with([
                    'category',
                    'units.measurementUnit',
                    'prices.priceList',
                    'saleTax',
                    'purchaseTax',
                ])
                ->where('company_id', $companyId)
                ->orderBy('code')
                ->get();

            foreach ($items as $item) {
                $baseUnit = $item->units->firstWhere('is_base', 'yes');
                $price = $item->prices->firstWhere('status', 'active') ?? $item->prices->first();

                $rowValues = [
                    'sku' => (string) $item->sku,
                    'nombre' => (string) $item->name,
                    'codigo_barras' => (string) ($item->barcode ?? ''),
                    'precio' => $price !== null ? (float) $price->price : '',
                    'moneda' => $price !== null ? $price->currency : 'USD',
                    'lista_precio' => $price?->priceList?->name ?? '',
                    'costo_estandar' => (float) $item->standard_cost,
                    'precio_minimo' => (float) $item->min_price,
                    'tipo' => (string) $item->type,
                    'categoria' => $item->category?->name ?? '',
                    'unidad_base' => $baseUnit?->measurementUnit?->abbreviation ?? $baseUnit?->measurementUnit?->name ?? '',
                    'impuesto_venta' => $item->saleTax?->name ?? '',
                    'impuesto_compra' => $item->purchaseTax?->name ?? '',
                    'descripcion' => (string) ($item->description ?? ''),
                    'notas' => (string) ($item->notes ?? ''),
                ];

                $this->writeRow($sheet, $rowNum, $rowValues);
                $rowNum++;
            }
        } else {
            // Filas de ejemplo instructivas
            $sampleRows = [
                [
                    'sku' => 'ZARC-001',
                    'nombre' => 'Zarcillos Esmeralda',
                    'codigo_barras' => '7501234567890',
                    'precio' => 25.00,
                    'moneda' => 'USD',
                    'lista_precio' => 'Precio regular',
                    'costo_estandar' => 12.50,
                    'precio_minimo' => 18.00,
                    'tipo' => 'inventoried',
                    'categoria' => 'Joyería',
                    'unidad_base' => 'UND',
                    'impuesto_venta' => 'IVA General (16%)',
                    'impuesto_compra' => 'IVA General (16%)',
                    'descripcion' => 'Zarcillos con baño en oro y esmeraldas',
                    'notas' => 'Cuidar manipulación',
                ],
                [
                    'sku' => 'SERV-REP-01',
                    'nombre' => 'Servicio de Reparación',
                    'codigo_barras' => '',
                    'precio' => 45.00,
                    'moneda' => 'USD',
                    'lista_precio' => 'Precio regular',
                    'costo_estandar' => 0.00,
                    'precio_minimo' => 35.00,
                    'tipo' => 'service',
                    'categoria' => 'Servicios',
                    'unidad_base' => 'SERV',
                    'impuesto_venta' => 'Exento (0%)',
                    'impuesto_compra' => 'Exento (0%)',
                    'descripcion' => 'Mantenimiento y pulitura general',
                    'notas' => 'Mano de obra',
                ],
            ];

            foreach ($sampleRows as $sample) {
                $this->writeRow($sheet, $rowNum, $sample);
                $rowNum++;
            }
        }

        // Bordes suaves para el contenido
        if ($rowNum > 2) {
            $contentEnd = $rowNum - 1;
            $sheet->getStyle("A2:{$lastColLetter}{$contentEnd}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_HAIR,
                        'color' => ['rgb' => 'D1D5DB'],
                    ],
                ],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function writeRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $rowNum, array $data): void
    {
        foreach (self::COLUMNS as $colIndex => $col) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $val = $data[$col['key']] ?? '';

            if (is_numeric($val) && in_array($col['key'], ['precio', 'costo_estandar', 'precio_minimo'], true)) {
                $sheet->setCellValueExplicit("{$colLetter}{$rowNum}", (float) $val, DataType::TYPE_NUMERIC);
                $sheet->getStyle("{$colLetter}{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');
            } else {
                $sheet->setCellValueExplicit("{$colLetter}{$rowNum}", (string) $val, DataType::TYPE_STRING);
            }
        }
    }

    private function buildReferenceSheet(Spreadsheet $spreadsheet, string $companyId): void
    {
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Catálogos de Referencia');

        $sectionHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '475569']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        // 1. Unidades de Medida
        $refSheet->setCellValue('A1', 'UNIDAD MEDIDA (Abrev)');
        $refSheet->setCellValue('B1', 'NOMBRE UNIDAD');
        $refSheet->getStyle('A1:B1')->applyFromArray($sectionHeaderStyle);
        $units = MeasurementUnit::where('company_id', $companyId)->where('status', 'active')->get();
        $r = 2;
        foreach ($units as $u) {
            $refSheet->setCellValue("A{$r}", $u->abbreviation);
            $refSheet->setCellValue("B{$r}", $u->name);
            $r++;
        }

        // 2. Listas de Precio
        $refSheet->setCellValue('D1', 'LISTAS DE PRECIO');
        $refSheet->setCellValue('E1', 'CÓDIGO');
        $refSheet->getStyle('D1:E1')->applyFromArray($sectionHeaderStyle);
        $priceLists = PriceList::where('company_id', $companyId)->where('status', 'active')->get();
        $r = 2;
        foreach ($priceLists as $p) {
            $refSheet->setCellValue("D{$r}", $p->name);
            $refSheet->setCellValue("E{$r}", $p->code);
            $r++;
        }

        // 3. Monedas Activas
        $refSheet->setCellValue('G1', 'MONEDAS VÁLIDAS');
        $refSheet->getStyle('G1')->applyFromArray($sectionHeaderStyle);
        $currencies = $this->currencyRepository->activeCodes();
        $r = 2;
        foreach ($currencies as $c) {
            $refSheet->setCellValue("G{$r}", $c);
            $r++;
        }

        // 4. Categorías
        $refSheet->setCellValue('I1', 'CATEGORÍAS');
        $refSheet->setCellValue('J1', 'CÓDIGO');
        $refSheet->getStyle('I1:J1')->applyFromArray($sectionHeaderStyle);
        $categories = Category::where('company_id', $companyId)->where('status', 'active')->get();
        $r = 2;
        foreach ($categories as $cat) {
            $refSheet->setCellValue("I{$r}", $cat->name);
            $refSheet->setCellValue("J{$r}", $cat->code);
            $r++;
        }

        // 5. Impuestos
        $refSheet->setCellValue('L1', 'IMPUESTO');
        $refSheet->setCellValue('M1', 'TASA %');
        $refSheet->getStyle('L1:M1')->applyFromArray($sectionHeaderStyle);
        $taxes = Tax::where('company_id', $companyId)->where('status', 'active')->get();
        $r = 2;
        foreach ($taxes as $tax) {
            $refSheet->setCellValue("L{$r}", $tax->name);
            $refSheet->setCellValue("M{$r}", "{$tax->percentage}%");
            $r++;
        }

        // 6. Tipos de artículo
        $refSheet->setCellValue('O1', 'TIPO ARTÍCULO (Valor)');
        $refSheet->setCellValue('P1', 'DESCRIPCIÓN');
        $refSheet->getStyle('O1:P1')->applyFromArray($sectionHeaderStyle);
        $types = [
            ['key' => 'inventoried', 'desc' => 'Inventariado (Mueve existencias)'],
            ['key' => 'non_inventoried', 'desc' => 'No inventariado (Sin stock)'],
            ['key' => 'service', 'desc' => 'Servicio (Mano de obra, intangible)'],
            ['key' => 'serialized', 'desc' => 'Serializado (Control por número de serie)'],
        ];
        $r = 2;
        foreach ($types as $t) {
            $refSheet->setCellValue("O{$r}", $t['key']);
            $refSheet->setCellValue("P{$r}", $t['desc']);
            $r++;
        }

        // Ajustar anchos automáticos en hoja de referencia
        foreach (['A', 'B', 'D', 'E', 'G', 'I', 'J', 'L', 'M', 'O', 'P'] as $col) {
            $refSheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
