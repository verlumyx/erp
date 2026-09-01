<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Client\Services\ClientApplyBalanceService;
use App\Modules\ClientAdvance\Commands\SearchClientAdvanceCommand;
use App\Modules\ClientAdvance\Commands\UpdateStatusClientAdvanceCommand;
use App\Modules\ClientAdvance\Exceptions\ClientAdvanceNotFoundException;
use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ClientAdvance\Repositories\Contracts\ClientAdvanceRepositoryInterface;
use App\Modules\ClientAdvance\Services\ClientAdvanceCollectionSyncService;
use App\Modules\ClientAdvance\Services\ClientAdvanceFindService;
use App\Modules\ClientAdvance\Services\ClientAdvanceOrderService;
use App\Modules\ClientAdvance\Services\ClientAdvanceSearchService;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

/** Un anticipo en memoria, sin tocar la base. */
function clientAdvanceDouble(array $attributes = []): ClientAdvance
{
    $advance = new ClientAdvance([
        'code' => 'ANC000001',
        'company_id' => 'company-uuid',
        'client_id' => 'client-uuid',
        'amount' => 400,
        'applied_amount' => 0,
        'balance' => 400,
        'status' => 'pending_confirmation',
        ...$attributes,
    ]);

    $advance->id = $attributes['id'] ?? 'advance-uuid';

    return $advance;
}

/** El cobro espejo de ese anticipo, también en memoria. */
function mirrorCollectionDouble(): ClientCollection
{
    return new ClientCollection([
        'company_id' => 'company-uuid',
        'client_id' => 'client-uuid',
        'origin_type' => ClientCollection::ORIGIN_ADVANCE,
        'origin_id' => 'advance-uuid',
        'amount' => 400,
    ]);
}

/** El servicio de pedido con el repositorio de pedidos simulado. */
function clientAdvanceOrderServiceReturning(?SalesOrder $order): ClientAdvanceOrderService
{
    $repository = Mockery::mock(SalesOrderRepositoryInterface::class);
    $repository->allows('findById')->andReturn($order);

    return new ClientAdvanceOrderService($repository);
}

test('an advance without a sales order needs no check', function () {
    clientAdvanceOrderServiceReturning(null)->guardSalesOrder(null, 'client-uuid', 'company-uuid');
})->throwsNoExceptions();

test('the sales order has to exist in the company', function () {
    clientAdvanceOrderServiceReturning(null)->guardSalesOrder('order-uuid', 'client-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('the sales order has to be of the same client', function () {
    $order = new SalesOrder(['client_id' => 'another-client']);

    clientAdvanceOrderServiceReturning($order)->guardSalesOrder('order-uuid', 'client-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('the find service fails when the advance does not exist', function () {
    $repository = Mockery::mock(ClientAdvanceRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    (new ClientAdvanceFindService($repository))->execute('missing-uuid');
})->throws(ClientAdvanceNotFoundException::class);

test('the search service delegates on the repository', function () {
    $command = new SearchClientAdvanceCommand(filters: ['status' => 'confirmed']);

    $repository = Mockery::mock(ClientAdvanceRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn(['data' => [], 'total' => 0]);

    expect((new ClientAdvanceSearchService($repository))->execute($command))
        ->toBe(['data' => [], 'total' => 0]);
});

test('confirming the collection moves the advance and credits the client', function () {
    $advance = clientAdvanceDouble();

    $repository = Mockery::mock(ClientAdvanceRepositoryInterface::class);
    $repository->expects('lockById')->andReturn($advance);
    $repository->expects('updateStatus')->withArgs(
        fn (ClientAdvance $model, UpdateStatusClientAdvanceCommand $command): bool => $command->status === 'confirmed',
    );
    $repository->expects('findOrFail')->andReturn($advance);

    $balances = Mockery::mock(ClientApplyBalanceService::class);
    $balances->expects('execute')->withArgs(
        fn ($command): bool => $command->advanceBalanceDelta === 400.0,
    )->andReturn(new Client);

    (new ClientAdvanceCollectionSyncService($repository, $balances))->confirm(mirrorCollectionDouble());
});

test('an advance that is not waiting for its collection cannot be confirmed', function () {
    $repository = Mockery::mock(ClientAdvanceRepositoryInterface::class);
    $repository->expects('lockById')->andReturn(clientAdvanceDouble(['status' => 'draft']));

    $balances = Mockery::mock(ClientApplyBalanceService::class);

    (new ClientAdvanceCollectionSyncService($repository, $balances))->confirm(mirrorCollectionDouble());
})->throws(ValidationException::class);

test('cancelling a received collection takes back the credit of the client', function () {
    $advance = clientAdvanceDouble(['status' => 'confirmed']);

    $repository = Mockery::mock(ClientAdvanceRepositoryInterface::class);
    $repository->expects('lockById')->andReturn($advance);
    $repository->expects('updateStatus')->withArgs(
        fn (ClientAdvance $model, UpdateStatusClientAdvanceCommand $command): bool => $command->status === 'draft',
    );
    $repository->expects('findOrFail')->andReturn($advance);

    $balances = Mockery::mock(ClientApplyBalanceService::class);
    $balances->expects('execute')->withArgs(
        fn ($command): bool => $command->advanceBalanceDelta === -400.0,
    )->andReturn(new Client);

    (new ClientAdvanceCollectionSyncService($repository, $balances))->revert(mirrorCollectionDouble());
});

/** Comprometido nunca dio crédito: revertirlo no se lo quita a nadie. */
test('cancelling a draft collection moves no balance', function () {
    $advance = clientAdvanceDouble();

    $repository = Mockery::mock(ClientAdvanceRepositoryInterface::class);
    $repository->expects('lockById')->andReturn($advance);
    $repository->expects('updateStatus');
    $repository->expects('findOrFail')->andReturn($advance);

    $balances = Mockery::mock(ClientApplyBalanceService::class);
    $balances->expects('execute')->never();

    (new ClientAdvanceCollectionSyncService($repository, $balances))->revert(mirrorCollectionDouble());
});

test('an advance already applied to invoices cannot be reverted', function () {
    $repository = Mockery::mock(ClientAdvanceRepositoryInterface::class);
    $repository->expects('lockById')->andReturn(clientAdvanceDouble([
        'status' => 'confirmed',
        'applied_amount' => 100,
    ]));

    $balances = Mockery::mock(ClientApplyBalanceService::class);

    (new ClientAdvanceCollectionSyncService($repository, $balances))->revert(mirrorCollectionDouble());
})->throws(ValidationException::class);
