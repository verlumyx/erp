<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dispatch\Commands\UpdateDispatchCommand;
use App\Modules\Dispatch\Requests\UpdateDispatchRequest;
use App\Modules\Dispatch\Services\DispatchUpdateService;
use Illuminate\Http\RedirectResponse;

class DispatchPutController extends Controller
{
    public function __construct(
        private readonly DispatchUpdateService $updateService,
    ) {}

    public function __invoke(UpdateDispatchRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateDispatchCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('dispatches.show', ['company' => $company, 'id' => $id]);
    }
}
