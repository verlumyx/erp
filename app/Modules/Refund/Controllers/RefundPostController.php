<?php

declare(strict_types=1);

namespace App\Modules\Refund\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Refund\Commands\CreateRefundCommand;
use App\Modules\Refund\Requests\CreateRefundRequest;
use App\Modules\Refund\Services\RefundCreateService;
use Illuminate\Http\RedirectResponse;

class RefundPostController extends Controller
{
    public function __construct(
        private readonly RefundCreateService $createService,
    ) {}

    public function __invoke(CreateRefundRequest $request): RedirectResponse
    {
        $refund = $this->createService->execute(
            CreateRefundCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()
            ->route('refunds.show', ['company' => $request->route('company'), 'id' => $refund->id])
            ->with('success', 'Reembolso registrado correctamente.');
    }
}
