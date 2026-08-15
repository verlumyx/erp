<?php

declare(strict_types=1);

namespace App\Modules\Service\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Service\Commands\CreateServiceCommand;
use App\Modules\Service\Requests\CreateServiceRequest;
use App\Modules\Service\Services\ServiceCreateService;
use Illuminate\Http\RedirectResponse;

class ServicePostController extends Controller
{
    public function __construct(
        private readonly ServiceCreateService $createService,
    ) {}

    public function __invoke(CreateServiceRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateServiceCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('services.index', ['company' => $request->route('company')]);
    }
}
