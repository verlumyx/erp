<?php

declare(strict_types=1);

namespace App\Modules\Lead\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Lead\Commands\CreateLeadCommand;
use App\Modules\Lead\Requests\CreateLeadRequest;
use App\Modules\Lead\Services\LeadCreateService;
use Illuminate\Http\RedirectResponse;

class LeadPostController extends Controller
{
    public function __construct(
        private readonly LeadCreateService $createService,
    ) {}

    public function __invoke(CreateLeadRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateLeadCommand::fromRequest($request)
        );

        return redirect()->route('contact')
            ->with('success', 'Gracias por contactarnos. Te responderemos pronto.');
    }
}
