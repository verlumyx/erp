<?php

declare(strict_types=1);

namespace App\Modules\Category\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Category\Commands\CreateCategoryCommand;
use App\Modules\Category\Requests\CreateCategoryRequest;
use App\Modules\Category\Services\CategoryCreateService;
use Illuminate\Http\RedirectResponse;

class CategoryPostController extends Controller
{
    public function __construct(
        private readonly CategoryCreateService $createService,
    ) {}

    public function __invoke(CreateCategoryRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateCategoryCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('categories.index', ['company' => $request->route('company')]);
    }
}
