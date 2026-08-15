<?php

declare(strict_types=1);

namespace App\Modules\Service\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Service\Commands\UpdateServiceCommand;
use App\Modules\Service\Requests\UpdateServiceRequest;
use App\Modules\Service\Services\ServiceUpdateService;
use Illuminate\Http\RedirectResponse;

class ServicePutController extends Controller
{
    public function __construct(
        private readonly ServiceUpdateService $updateService,
    ) {}

    public function __invoke(UpdateServiceRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateServiceCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('services.show', ['company' => $company, 'id' => $id]);
    }
}
