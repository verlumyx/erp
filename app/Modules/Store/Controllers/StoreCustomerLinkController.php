<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Store\Commands\LinkStoreCustomerCommand;
use App\Modules\Store\Requests\LinkStoreCustomerRequest;
use App\Modules\Store\Services\StoreCustomerLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreCustomerLinkController extends Controller
{
    public function __construct(
        private readonly StoreCustomerLinkService $linkService,
    ) {}

    public function __invoke(LinkStoreCustomerRequest $request, string $company, string $id): RedirectResponse
    {
        try {
            $customer = DB::transaction(fn () => $this->linkService->execute(
                LinkStoreCustomerCommand::fromRequest($request, $company, $id),
            ));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return back()->with('error', 'No se pudo vincular el comprador: '.$exception->getMessage());
        }

        return redirect()
            ->route('store-customers.show', ['company' => $company, 'id' => $customer->id])
            ->with('success', "Comprador {$customer->code} vinculado al cliente {$customer->client?->name}.");
    }
}
