<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dispatch\Commands\CreateDispatchCommand;
use App\Modules\Dispatch\Requests\CreateDispatchRequest;
use App\Modules\Dispatch\Services\DispatchCreateService;
use Illuminate\Http\RedirectResponse;

class DispatchPostController extends Controller
{
    public function __construct(
        private readonly DispatchCreateService $createService,
    ) {}

    public function __invoke(CreateDispatchRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateDispatchCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('dispatches.index', ['company' => $request->route('company')]);
    }
}
