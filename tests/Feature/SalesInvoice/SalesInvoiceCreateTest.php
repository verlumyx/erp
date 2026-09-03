<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\Tax\Models\Tax;

use function Pest\Laravel\actingAs;

test('a sales invoice can be created with its lines', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        'invoice_series' => 'A',
        'sale_type' => 'credit',
        'due_date' => now()->addDays(30)->toDateString(),
        'notes' => 'Cancelar en la fecha pactada.',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('sales-invoices.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $invoice = SalesInvoice::with('lines')->find($payload['id']);
    expect($invoice)->not->toBeNull();
    expect($invoice->code)->toBe('FVE000001');
    expect($invoice->status)->toBe('draft');
    expect($invoice->company_id)->toBe($company->id);
    expect($invoice->created_by)->toBe($user->id);
    expect($invoice->invoice_series)->toBe('A');
    expect($invoice->payment_status)->toBe('pending');
    expect($invoice->lines)->toHaveCount(1);

    /** El correlativo fiscal se quema al confirmar, no al capturar. */
    expect($invoice->invoice_number)->toBeNull();

    $line = $invoice->lines->first();
    expect($line->line_number)->toBe(1);
    expect($line->company_id)->toBe($company->id);
    expect((float) $line->quantity)->toBe(2.0);
    expect((float) $line->base_quantity)->toBe(2.0);
    expect((float) $line->unit_cost)->toBe(0.0);
    expect($line->status)->toBe('active');
});

test('the totals are computed from the lines and never taken from the payload', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        /** Importes inventados: el backend los ignora y recalcula. */
        'subtotal' => 999999,
        'total' => 999999,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 50,
                'discount_percent' => 10,
                'tax_percent' => 16,
                'withholding_percent' => 75,
            ],
        ],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $invoice = SalesInvoice::with('lines')->find($payload['id']);
    $line = $invoice->lines->first();

    // 10 × 50 = 500 bruto; 10% de descuento = 50; base 450; IVA 16% = 72.
    expect((float) $line->discount_amount)->toBe(50.0);
    expect((float) $line->subtotal)->toBe(450.0);
    expect((float) $line->tax_amount)->toBe(72.0);
    /** La retención se practica sobre el impuesto: 75% de 72. */
    expect((float) $line->withholding_amount)->toBe(54.0);
    expect((float) $line->total)->toBe(522.0);

    expect((float) $invoice->subtotal)->toBe(450.0);
    expect((float) $invoice->discount_amount)->toBe(50.0);
    expect((float) $invoice->tax_amount)->toBe(72.0);
    expect((float) $invoice->withholding_amount)->toBe(54.0);
    /** La retención no baja el total: la salda el cliente con su comprobante. */
    expect((float) $invoice->total)->toBe(522.0);
    expect((float) $invoice->balance)->toBe(522.0);
});

test('the bolivar amounts are frozen because an invoice has legal value', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    // 2 × 100 = 200 a la tasa 36,5 del escenario.
    expect((float) $invoice->exchange_rate)->toBe(36.5);
    expect((float) $invoice->subtotal_ves)->toBe(7300.0);
    expect((float) $invoice->total_ves)->toBe(7300.0);
    expect((float) $invoice->tax_amount_ves)->toBe(0.0);
});

test('the line tax percent is applied and its withholding falls on the tax', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $tax = Tax::factory()->create([
        'company_id' => $company->id,
        'percentage' => 16,
        'has_withholding' => 'yes',
        'withholding_percentage' => 75,
        'status' => 'active',
    ]);

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 100,
                'tax_id' => $tax->id,
                'tax_percent' => 16,
                'withholding_percent' => 75,
            ],
        ],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = SalesInvoice::with('lines')->find($payload['id'])->lines->first();

    expect($line->tax_id)->toBe($tax->id);
    expect((float) $line->tax_amount)->toBe(16.0);
    expect((float) $line->withholding_amount)->toBe(12.0);
});

test('an invoice needs at least one line', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, ['lines' => []]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines');

    expect(SalesInvoice::count())->toBe(0);
});

test('the same item cannot be repeated in two lines with the same unit', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 10],
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 2, 'unit_price' => 10],
        ],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.1.item_id');

    expect(SalesInvoice::count())->toBe(0);
});

test('the due date cannot be earlier than the invoice date', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->subDay()->toDateString(),
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('due_date');

    expect(SalesInvoice::count())->toBe(0);
});

test('a client from another company is rejected', function () {
    [$user, $company, , $warehouse, $item, $unit] = salesInvoiceScenario();
    [, $otherCompany] = createUserWithCompany();

    $stranger = Client::factory()->create(['company_id' => $otherCompany->id]);

    $payload = salesInvoicePayload($stranger, $warehouse, $item, $unit);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('client_id');

    expect(SalesInvoice::count())->toBe(0);
});

test('a delivery address of another client is rejected', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $otherClient = Client::factory()->create(['company_id' => $company->id]);
    $address = ClientAddress::factory()->create([
        'company_id' => $company->id,
        'client_id' => $otherClient->id,
    ]);

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        'client_address_id' => $address->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('client_address_id');
});

test('a non sellable item is rejected', function () {
    [$user, $company, $client, $warehouse, , $unit] = salesInvoiceScenario();

    $item = Item::factory()->create([
        'company_id' => $company->id,
        'is_sellable' => 'no',
    ]);

    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.item_id');
});

test('the quantity is converted to the base unit of the item', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'is_base' => 'no',
        'conversion_factor' => 12,
    ]);

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $box->id,
                'quantity' => 3,
                'unit_price' => 100,
            ],
        ],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = SalesInvoice::with('lines')->find($payload['id'])->lines->first();

    expect((float) $line->quantity)->toBe(3.0);
    expect((float) $line->base_quantity)->toBe(36.0);
});

test('the codes are sequential per company', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $first = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $second = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    expect($first->code)->toBe('FVE000001');
    expect($second->code)->toBe('FVE000002');
});

test('a user without the create permission is rejected', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    assignRoleWithPermissions($user, $company, ['sales-invoices.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-invoices.store', ['company' => $company->id]),
            salesInvoicePayload($client, $warehouse, $item, $unit),
        )
        ->assertForbidden();

    expect(SalesInvoice::count())->toBe(0);
});
