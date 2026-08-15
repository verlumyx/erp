<?php

declare(strict_types=1);

namespace App\Modules\Sale\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sale\Commands\CreateSaleCommand;
use App\Modules\Sale\Requests\CreateSaleRequest;
use App\Modules\Sale\Services\SaleCreateService;
use Illuminate\Http\RedirectResponse;

class SalePostController extends Controller
{
    public function __construct(
        private readonly SaleCreateService $createService,
    ) {}

    public function __invoke(CreateSaleRequest $request): RedirectResponse
    {
        $sale = $this->createService->execute(
            CreateSaleCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()
            ->route('sales.show', ['company' => $request->route('company'), 'id' => $sale->id])
            ->with('success', 'Venta registrada correctamente.');
    }
}
