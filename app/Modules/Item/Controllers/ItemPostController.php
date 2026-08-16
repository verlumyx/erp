<?php

declare(strict_types=1);

namespace App\Modules\Item\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Item\Commands\CreateItemCommand;
use App\Modules\Item\Requests\CreateItemRequest;
use App\Modules\Item\Services\ItemCreateService;
use Illuminate\Http\RedirectResponse;

class ItemPostController extends Controller
{
    public function __construct(
        private readonly ItemCreateService $createService,
    ) {}

    public function __invoke(CreateItemRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateItemCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('items.index', ['company' => $request->route('company')]);
    }
}
