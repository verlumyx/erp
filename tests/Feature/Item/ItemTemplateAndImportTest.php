<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\PriceList\Models\PriceList;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use function Pest\Laravel\actingAs;

function createTestExcelFile(array $headers, array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($headers as $colIdx => $header) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
        $sheet->setCellValue("{$colLetter}1", $header);
    }

    foreach ($rows as $rowIdx => $row) {
        $rowNum = $rowIdx + 2;
        foreach ($row as $colIdx => $val) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}{$rowNum}", $val);
        }
    }

    $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_').'.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempPath);

    return new UploadedFile(
        $tempPath,
        'test_articulos.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}

test('user can download blank item template', function () {
    [$user, $company] = itemScenario();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('items.template', ['company' => $company->id, 'type' => 'blank']));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('plantilla_articulos.xlsx');

    $keys = array_column(\App\Modules\Item\Services\ItemTemplateService::COLUMNS, 'key');
    expect($keys)->not->toContain('codigo_interno');
    expect($keys)->not->toContain('metodo_costo');
});

test('user can download template with existing items', function () {
    [$user, $company, $unit] = itemScenario();

    $item = Item::factory()->create([
        'company_id' => $company->id,
        'sku' => 'SKU-TEST-01',
        'name' => 'Artículo para exportar',
        'code' => 'ART000001',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('items.template', ['company' => $company->id, 'type' => 'current']));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('catalogo_articulos_');
});

test('import creates a new item when sku does not exist', function () {
    [$user, $company, $unit] = itemScenario();
    $priceList = PriceList::factory()->create(['company_id' => $company->id, 'name' => 'Precio regular']);

    $headers = ['SKU (*)', 'Nombre (*)', 'Precio', 'Moneda', 'Lista de precio', 'Unidad base'];
    $rows = [
        ['NUEVO-001', 'Taladro Percutor 750W', 85.50, 'USD', 'Precio regular', $unit->abbreviation],
    ];

    $file = createTestExcelFile($headers, $rows);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.import', ['company' => $company->id]), [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['created'])->toBe(1);
    expect($data['updated'])->toBe(0);
    expect($data['errors'])->toBeEmpty();

    $item = Item::where('company_id', $company->id)->where('sku', 'NUEVO-001')->first();
    expect($item)->not->toBeNull();
    expect($item->name)->toBe('Taladro Percutor 750W');
    expect($item->code)->toBe('ART000001');

    // Verifica que se creó la unidad base
    $itemUnit = $item->units->first();
    expect($itemUnit)->not->toBeNull();
    expect($itemUnit->measurement_unit_id)->toBe($unit->id);
    expect($itemUnit->is_base)->toBe('yes');

    // Verifica que se creó el precio
    $itemPrice = $item->prices->first();
    expect($itemPrice)->not->toBeNull();
    expect((float) $itemPrice->price)->toBe(85.5);
    expect($itemPrice->currency)->toBe('USD');
});

test('import updates price and details when sku already exists', function () {
    [$user, $company, $unit] = itemScenario();
    $priceList = PriceList::factory()->create(['company_id' => $company->id, 'name' => 'Precio regular']);

    // Crear artículo existente con precio inicial 20.00
    $payload = itemPayload($unit, [
        'sku' => 'EXIST-001',
        'name' => 'Zarcillos Originales',
        'prices' => [
            [
                'price_list_id' => $priceList->id,
                'price' => 20.00,
                'currency' => 'USD',
                'status' => 'active',
            ],
        ],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), $payload);

    $existing = Item::where('company_id', $company->id)->where('sku', 'EXIST-001')->first();
    expect($existing)->not->toBeNull();
    $originalCode = $existing->code;
    $originalId = $existing->id;

    // Subir Excel con nuevo precio 49.99 para el mismo SKU
    $headers = ['SKU (*)', 'Nombre (*)', 'Precio', 'Moneda', 'Lista de precio'];
    $rows = [
        ['EXIST-001', 'Zarcillos Originales (Actualizado)', 49.99, 'USD', 'Precio regular'],
    ];

    $file = createTestExcelFile($headers, $rows);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.import', ['company' => $company->id]), [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['created'])->toBe(0);
    expect($data['updated'])->toBe(1);
    expect($data['errors'])->toBeEmpty();

    $reloaded = Item::find($originalId);
    expect($reloaded->code)->toBe($originalCode);
    expect($reloaded->name)->toBe('Zarcillos Originales (Actualizado)');

    // Precio actualizado
    $updatedPrice = $reloaded->prices()->where('price_list_id', $priceList->id)->first();
    expect((float) $updatedPrice->price)->toBe(49.99);
});

test('import handles invalid rows tolerantly and reports errors', function () {
    [$user, $company, $unit] = itemScenario();

    $headers = ['SKU (*)', 'Nombre (*)', 'Precio'];
    $rows = [
        ['VALID-01', 'Producto Válido', 10.00],
        ['', 'Sin SKU', 15.00],
        ['INVALID-NO-NAME', '', 20.00],
    ];

    $file = createTestExcelFile($headers, $rows);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.import', ['company' => $company->id]), [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['created'])->toBe(1);
    expect($data['updated'])->toBe(0);
    expect($data['skipped'])->toBe(2);
    expect($data['errors'])->toHaveCount(2);

    expect(Item::where('company_id', $company->id)->where('sku', 'VALID-01')->exists())->toBeTrue();
});
