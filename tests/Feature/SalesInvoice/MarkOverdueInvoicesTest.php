<?php

declare(strict_types=1);

use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\Supplier\Models\Supplier;

use function Pest\Laravel\artisan;

test('an invoice that expired untouched is marked overdue', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    /**
     * La factura se emitió con su vencimiento por delante y venció después, sin
     * que nadie la volviera a tocar: es el caso que el comando resuelve.
     */
    SalesInvoice::where('id', $invoice->id)->update([
        'due_date' => now()->subDays(30)->toDateString(),
        'payment_status' => 'pending',
    ]);

    artisan('invoices:mark-overdue')->assertSuccessful();

    expect($invoice->refresh()->payment_status)->toBe('overdue');
});

test('an invoice that has not expired yet is left alone', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    /** Vence hoy: vence al terminar el día, así que todavía no está vencida. */
    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'due_date' => now()->toDateString(),
    ]);

    artisan('invoices:mark-overdue')->assertSuccessful();

    expect($invoice->refresh()->payment_status)->toBe('pending');
});

test('a settled invoice is never marked overdue', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    SalesInvoice::where('id', $invoice->id)->update([
        'due_date' => now()->subDays(30)->toDateString(),
        'paid_amount' => $invoice->total,
        'balance' => 0,
        'payment_status' => 'paid',
    ]);

    artisan('invoices:mark-overdue')->assertSuccessful();

    expect($invoice->refresh()->payment_status)->toBe('paid');
});

test('a draft invoice owes nothing yet and is left alone', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    SalesInvoice::where('id', $invoice->id)
        ->update(['due_date' => now()->subDays(30)->toDateString()]);

    artisan('invoices:mark-overdue')->assertSuccessful();

    expect($invoice->refresh()->payment_status)->toBe('pending');
});

test('a purchase invoice that expired untouched is marked overdue too', function () {
    [$user, $company, , $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $supplier = Supplier::factory()->create([
        'company_id' => $company->id,
        'current_balance' => 0,
        'payment_term_days' => 0,
    ]);

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    PurchaseInvoice::where('id', $invoice->id)
        ->update(['due_date' => now()->subDays(30)->toDateString()]);

    expect($invoice->refresh()->payment_status)->toBe('pending');

    artisan('invoices:mark-overdue')->assertSuccessful();

    expect(PurchaseInvoice::find($invoice->id)->payment_status)->toBe('overdue');
});

test('the cut-off date can be moved back', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    SalesInvoice::where('id', $invoice->id)->update([
        'due_date' => now()->subDays(5)->toDateString(),
        'payment_status' => 'pending',
    ]);

    /** Al corte de hace diez días esa factura todavía no había vencido. */
    artisan('invoices:mark-overdue', ['--date' => now()->subDays(10)->toDateString()])
        ->assertSuccessful();

    expect($invoice->refresh()->payment_status)->toBe('pending');
});
