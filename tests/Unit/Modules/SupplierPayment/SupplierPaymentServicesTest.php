<?php

declare(strict_types=1);

use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use App\Modules\SupplierPayment\Commands\SearchSupplierPaymentCommand;
use App\Modules\SupplierPayment\Commands\SupplierPaymentApplicationData;
use App\Modules\SupplierPayment\Commands\UpdateStatusSupplierPaymentCommand;
use App\Modules\SupplierPayment\Exceptions\SupplierPaymentNotFoundException;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Repositories\Contracts\SupplierPaymentRepositoryInterface;
use App\Modules\SupplierPayment\Services\SupplierPaymentFindService;
use App\Modules\SupplierPayment\Services\SupplierPaymentOriginService;
use App\Modules\SupplierPayment\Services\SupplierPaymentPostingService;
use App\Modules\SupplierPayment\Services\SupplierPaymentSearchService;
use App\Modules\SupplierPayment\Services\SupplierPaymentUpdateStatusService;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

/** Una factura de compra en memoria, sin tocar la base. */
function invoiceDouble(array $attributes = []): PurchaseInvoice
{
    $invoice = new PurchaseInvoice([
        'code' => 'FCO000001',
        'supplier_id' => 'supplier-uuid',
        'status' => 'confirmed',
        'total' => 250,
        'balance' => 250,
        ...$attributes,
    ]);

    $invoice->id = $attributes['id'] ?? 'invoice-uuid';

    return $invoice;
}

/** El servicio de origen con el repositorio de facturas simulado. */
function originServiceReturning(?PurchaseInvoice $invoice): SupplierPaymentOriginService
{
    $repository = Mockery::mock(PurchaseInvoiceRepositoryInterface::class);
    $repository->allows('findById')->andReturn($invoice);

    return new SupplierPaymentOriginService($repository);
}

test('the origin of a payment cannot be an advance from this screen', function () {
    originServiceReturning(null)->guardOrigin('advance', 'advance-uuid', 'supplier-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('a payment from a supplier does not point at any document', function () {
    originServiceReturning(null)->guardOrigin('supplier', 'invoice-uuid', 'supplier-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('a payment from an invoice needs that invoice', function () {
    originServiceReturning(null)->guardOrigin('invoice', null, 'supplier-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('the origin invoice has to exist in the company', function () {
    originServiceReturning(null)->guardOrigin('invoice', 'invoice-uuid', 'supplier-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('the origin invoice has to belong to the same supplier', function () {
    originServiceReturning(invoiceDouble(['supplier_id' => 'another-supplier']))
        ->guardOrigin('invoice', 'invoice-uuid', 'supplier-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('a draft invoice does not admit payments', function () {
    originServiceReturning(invoiceDouble(['status' => 'draft']))
        ->guardOrigin('invoice', 'invoice-uuid', 'supplier-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('a settled invoice does not admit payments', function () {
    originServiceReturning(invoiceDouble(['balance' => 0]))
        ->guardOrigin('invoice', 'invoice-uuid', 'supplier-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('a live invoice of the same supplier is a valid origin', function () {
    originServiceReturning(invoiceDouble())
        ->guardOrigin('invoice', 'invoice-uuid', 'supplier-uuid', 'company-uuid');

    expect(true)->toBeTrue();
});

test('an application cannot exceed the balance of its invoice', function () {
    originServiceReturning(invoiceDouble(['balance' => 100]))->guardApplications(
        [new SupplierPaymentApplicationData('invoice-uuid', 150)],
        'supplier-uuid',
        'company-uuid',
    );
})->throws(ValidationException::class);

test('a reversed row of the distribution is not checked', function () {
    originServiceReturning(null)->guardApplications(
        [new SupplierPaymentApplicationData('invoice-uuid', 150, 'reversed')],
        'supplier-uuid',
        'company-uuid',
    );

    expect(true)->toBeTrue();
});

test('finding a payment that does not exist fails', function () {
    $repository = Mockery::mock(SupplierPaymentRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    (new SupplierPaymentFindService($repository))->execute('missing-uuid');
})->throws(SupplierPaymentNotFoundException::class);

test('changing the status of a payment that does not exist fails', function () {
    $repository = Mockery::mock(SupplierPaymentRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $posting = Mockery::mock(SupplierPaymentPostingService::class);

    (new SupplierPaymentUpdateStatusService($repository, $posting))
        ->execute('missing-uuid', new UpdateStatusSupplierPaymentCommand('confirmed'));
})->throws(SupplierPaymentNotFoundException::class);

test('the search goes straight through the repository', function () {
    $command = new SearchSupplierPaymentCommand(filters: ['status' => 'draft']);
    $expected = ['data' => [new SupplierPayment], 'total' => 1];

    $repository = Mockery::mock(SupplierPaymentRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    expect((new SupplierPaymentSearchService($repository))->execute($command))->toBe($expected);
});
