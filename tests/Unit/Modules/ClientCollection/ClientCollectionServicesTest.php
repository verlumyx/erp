<?php

declare(strict_types=1);

use App\Modules\ClientCollection\Commands\ClientCollectionApplicationData;
use App\Modules\ClientCollection\Commands\SearchClientCollectionCommand;
use App\Modules\ClientCollection\Commands\UpdateStatusClientCollectionCommand;
use App\Modules\ClientCollection\Exceptions\ClientCollectionNotFoundException;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Repositories\Contracts\ClientCollectionRepositoryInterface;
use App\Modules\ClientCollection\Services\ClientCollectionFindService;
use App\Modules\ClientCollection\Services\ClientCollectionOriginService;
use App\Modules\ClientCollection\Services\ClientCollectionPostingService;
use App\Modules\ClientCollection\Services\ClientCollectionSearchService;
use App\Modules\ClientCollection\Services\ClientCollectionUpdateStatusService;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

/** Una factura de venta en memoria, sin tocar la base. */
function salesInvoiceDouble(array $attributes = []): SalesInvoice
{
    $invoice = new SalesInvoice([
        'code' => 'FVE000001',
        'client_id' => 'client-uuid',
        'status' => 'confirmed',
        'total' => 200,
        'balance' => 200,
        ...$attributes,
    ]);

    $invoice->id = $attributes['id'] ?? 'invoice-uuid';

    return $invoice;
}

/** El servicio de origen con el repositorio de facturas simulado. */
function collectionOriginServiceReturning(?SalesInvoice $invoice): ClientCollectionOriginService
{
    $repository = Mockery::mock(SalesInvoiceRepositoryInterface::class);
    $repository->allows('findById')->andReturn($invoice);

    return new ClientCollectionOriginService($repository);
}

test('the origin of a collection cannot be an advance from this screen', function () {
    collectionOriginServiceReturning(null)->guardOrigin('advance', 'advance-uuid', 'client-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('a collection from a client does not point at any document', function () {
    collectionOriginServiceReturning(null)->guardOrigin('client', 'invoice-uuid', 'client-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('a collection from an invoice needs that invoice', function () {
    collectionOriginServiceReturning(null)->guardOrigin('invoice', null, 'client-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('the origin invoice has to exist in the company', function () {
    collectionOriginServiceReturning(null)->guardOrigin('invoice', 'invoice-uuid', 'client-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('the origin invoice has to belong to the same client', function () {
    collectionOriginServiceReturning(salesInvoiceDouble(['client_id' => 'another-client']))
        ->guardOrigin('invoice', 'invoice-uuid', 'client-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('a draft invoice does not admit collections', function () {
    collectionOriginServiceReturning(salesInvoiceDouble(['status' => 'draft']))
        ->guardOrigin('invoice', 'invoice-uuid', 'client-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('a settled invoice does not admit collections', function () {
    collectionOriginServiceReturning(salesInvoiceDouble(['balance' => 0]))
        ->guardOrigin('invoice', 'invoice-uuid', 'client-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('a live invoice of the same client is a valid origin', function () {
    collectionOriginServiceReturning(salesInvoiceDouble())
        ->guardOrigin('invoice', 'invoice-uuid', 'client-uuid', 'company-uuid');

    expect(true)->toBeTrue();
});

test('an application cannot exceed the balance of its invoice', function () {
    collectionOriginServiceReturning(salesInvoiceDouble(['balance' => 100]))->guardApplications(
        [new ClientCollectionApplicationData('invoice-uuid', 150)],
        'client-uuid',
        'company-uuid',
    );
})->throws(ValidationException::class);

test('a reversed row of the distribution is not checked', function () {
    collectionOriginServiceReturning(null)->guardApplications(
        [new ClientCollectionApplicationData('invoice-uuid', 150, 'reversed')],
        'client-uuid',
        'company-uuid',
    );

    expect(true)->toBeTrue();
});

test('finding a collection that does not exist fails', function () {
    $repository = Mockery::mock(ClientCollectionRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    (new ClientCollectionFindService($repository))->execute('missing-uuid');
})->throws(ClientCollectionNotFoundException::class);

test('changing the status of a collection that does not exist fails', function () {
    $repository = Mockery::mock(ClientCollectionRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $posting = Mockery::mock(ClientCollectionPostingService::class);

    (new ClientCollectionUpdateStatusService($repository, $posting))
        ->execute('missing-uuid', new UpdateStatusClientCollectionCommand('confirmed'));
})->throws(ClientCollectionNotFoundException::class);

test('the search goes straight through the repository', function () {
    $command = new SearchClientCollectionCommand(filters: ['status' => 'draft']);
    $expected = ['data' => [new ClientCollection], 'total' => 1];

    $repository = Mockery::mock(ClientCollectionRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    expect((new ClientCollectionSearchService($repository))->execute($command))->toBe($expected);
});
