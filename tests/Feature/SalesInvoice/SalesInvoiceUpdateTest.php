<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Models\SalesInvoiceLine;

use function Pest\Laravel\actingAs;

test('a draft invoice can be updated with its lines', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        'notes' => 'Se corrigió la cantidad acordada.',
        'freight_amount' => 20,
        'lines' => [
            [
                'id' => $invoice->lines->first()->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 5,
                'unit_price' => 80,
            ],
        ],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('sales-invoices.update', ['company' => $company->id, 'id' => $invoice->id]), $payload)
        ->assertRedirect(route('sales-invoices.show', ['company' => $company->id, 'id' => $invoice->id]))
        ->assertSessionHasNoErrors();

    $invoice->refresh()->load('lines');

    expect($invoice->notes)->toBe('Se corrigió la cantidad acordada.');
    expect((float) $invoice->subtotal)->toBe(400.0);
    expect((float) $invoice->total)->toBe(420.0);
    expect($invoice->lines)->toHaveCount(1);
    expect((float) $invoice->lines->first()->quantity)->toBe(5.0);
    /** El `code` no se recalcula al editar. */
    expect($invoice->code)->toBe('FVE000001');
});

test('a line that stops coming is deactivated, never deleted', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $other = Item::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $other->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 10],
            ['item_id' => $other->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 20],
        ],
    ]);

    $kept = $invoice->lines->firstWhere('item_id', $item->id);
    $dropped = $invoice->lines->firstWhere('item_id', $other->id);

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'id' => $kept->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 10,
            ],
        ],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('sales-invoices.update', ['company' => $company->id, 'id' => $invoice->id]), $payload)
        ->assertSessionHasNoErrors();

    expect(SalesInvoiceLine::find($dropped->id)->status)->toBe('inactive');
    expect(SalesInvoiceLine::find($kept->id)->line_number)->toBe(1);

    /** Los totales solo suman las líneas activas. */
    expect((float) $invoice->refresh()->total)->toBe(10.0);
});

test('a confirmed invoice can no longer be edited', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasNoErrors();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update', ['company' => $company->id, 'id' => $invoice->id]),
            salesInvoicePayload($client, $warehouse, $item, $unit, ['notes' => 'Tarde.']),
        )
        ->assertSessionHasErrors('status');

    expect($invoice->refresh()->notes)->toBeNull();
});

test('an invoice of another company cannot be reached', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();
    [, $otherCompany] = createUserWithCompany();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    /** El usuario no pertenece a esa empresa: `company.access` lo detiene. */
    actingAs($user)
        ->withSession(['current_company_id' => $otherCompany->id])
        ->put(
            route('sales-invoices.update', ['company' => $otherCompany->id, 'id' => $invoice->id]),
            salesInvoicePayload($client, $warehouse, $item, $unit),
        )
        ->assertForbidden();

    expect(SalesInvoice::find($invoice->id)->notes)->toBeNull();
});
