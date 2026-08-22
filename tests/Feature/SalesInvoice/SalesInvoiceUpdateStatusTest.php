<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Item\Models\Item;

use function Pest\Laravel\actingAs;

/** Emite la factura por HTTP, que es la única vía para confirmarla. */
function confirmSalesInvoice(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\SalesInvoice\Models\SalesInvoice $invoice,
): void {
    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasNoErrors();
}

test('confirming an invoice burns its fiscal number and posts the receivable', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'invoice_series' => 'A',
    ]);

    expect($invoice->invoice_number)->toBeNull();

    confirmSalesInvoice($user, $company, $invoice);

    $invoice->refresh();

    expect($invoice->status)->toBe('confirmed');
    expect($invoice->invoice_number)->toBe('00000001');
    expect($invoice->payment_status)->toBe('pending');
    expect((float) $invoice->balance)->toBe(200.0);

    /** La cuenta por cobrar del cliente crece con el saldo de la factura. */
    expect((float) Client::find($client->id)->current_balance)->toBe(200.0);
});

test('the fiscal number is correlative inside its series', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $first = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, ['invoice_series' => 'A']);
    $second = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, ['invoice_series' => 'A']);
    $otherSeries = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, ['invoice_series' => 'B']);

    confirmSalesInvoice($user, $company, $first);
    confirmSalesInvoice($user, $company, $second);
    confirmSalesInvoice($user, $company, $otherSeries);

    expect($first->refresh()->invoice_number)->toBe('00000001');
    expect($second->refresh()->invoice_number)->toBe('00000002');
    /** Cada serie lleva su propia cuenta. */
    expect($otherSeries->refresh()->invoice_number)->toBe('00000001');
});

test('confirming freezes the cost of the goods sold and its margin', function () {
    [$user, $company, $client, $warehouse, , $unit] = salesInvoiceScenario();

    $item = Item::factory()->create([
        'company_id' => $company->id,
        'cost_method' => 'average',
        'average_cost' => 30,
    ]);

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    confirmSalesInvoice($user, $company, $invoice);

    $line = $invoice->refresh()->load('lines')->lines->first();

    // 2 unidades a un costo promedio de 30.
    expect((float) $line->unit_cost)->toBe(30.0);
    expect((float) $line->total_cost)->toBe(60.0);
    expect((float) $line->margin_amount)->toBe(140.0);
    expect((float) $invoice->total_cost)->toBe(60.0);
});

test('the cost is left to the dispatch when the invoice does not move inventory', function () {
    [$user, $company, $client, $warehouse, , $unit] = salesInvoiceScenario();

    $item = Item::factory()->create([
        'company_id' => $company->id,
        'cost_method' => 'average',
        'average_cost' => 30,
    ]);

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'affects_inventory' => 'no',
    ]);

    confirmSalesInvoice($user, $company, $invoice);

    expect((float) $invoice->refresh()->total_cost)->toBe(0.0);
});

test('an invoice due in the past is born overdue', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    /** El documento se valora con la tasa de su fecha, no con la de hoy. */
    \App\Modules\ExchangeRate\Models\ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => now()->subDays(60)->toDateString(),
        'rate' => 35,
        'type' => 'legal',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'invoice_date' => now()->subDays(60)->toDateString(),
        'due_date' => now()->subDays(30)->toDateString(),
    ]);

    confirmSalesInvoice($user, $company, $invoice);

    expect($invoice->refresh()->payment_status)->toBe('overdue');
});

test('cancelling a confirmed invoice releases the receivable', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    confirmSalesInvoice($user, $company, $invoice);

    expect((float) Client::find($client->id)->current_balance)->toBe(200.0);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'cancelled', 'cancellation_reason' => 'Se facturó al cliente equivocado.'],
        )
        ->assertSessionHasNoErrors();

    $invoice->refresh();

    expect($invoice->status)->toBe('cancelled');
    expect($invoice->cancelled_at)->not->toBeNull();
    expect($invoice->cancellation_reason)->toBe('Se facturó al cliente equivocado.');
    expect((float) Client::find($client->id)->current_balance)->toBe(0.0);
});

test('cancelling a draft never touched the receivable', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'cancelled', 'cancellation_reason' => 'Se capturó por error.'],
        )
        ->assertSessionHasNoErrors();

    expect($invoice->refresh()->status)->toBe('cancelled');
    expect((float) Client::find($client->id)->current_balance)->toBe(0.0);
});

test('cancelling requires a reason', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'cancelled'],
        )
        ->assertSessionHasErrors('cancellation_reason');

    expect($invoice->refresh()->status)->toBe('draft');
});

test('an invoice with collections applied cannot be cancelled', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    confirmSalesInvoice($user, $company, $invoice);

    /** El cobro lo asentará el módulo de Cobros; aquí se simula su efecto. */
    $invoice->update(['paid_amount' => 50, 'balance' => 150, 'payment_status' => 'partial']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'cancelled', 'cancellation_reason' => 'Ya no aplica.'],
        )
        ->assertSessionHasErrors('status');

    expect($invoice->refresh()->status)->toBe('confirmed');
});

test('a cancelled invoice is final', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'cancelled', 'cancellation_reason' => 'Se capturó por error.'],
        )
        ->assertSessionHasNoErrors();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasErrors('status');

    expect($invoice->refresh()->status)->toBe('cancelled');
});

test('an invoice without active lines cannot be issued', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $invoice->lines()->update(['status' => 'inactive']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasErrors('status');

    expect($invoice->refresh()->status)->toBe('draft');
});

test('a user without the update-status permission is rejected', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    assignRoleWithPermissions($user, $company, ['sales-invoices.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'confirmed'],
        )
        ->assertForbidden();

    expect($invoice->refresh()->status)->toBe('draft');
});
