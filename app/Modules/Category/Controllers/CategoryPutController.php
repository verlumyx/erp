<?php

declare(strict_types=1);

namespace App\Modules\Category\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Category\Commands\UpdateCategoryCommand;
use App\Modules\Category\Requests\UpdateCategoryRequest;
use App\Modules\Category\Services\CategoryUpdateService;
use Illuminate\Http\RedirectResponse;

class CategoryPutController extends Controller
{
    public function __construct(
        private readonly CategoryUpdateService $updateService,
    ) {}

    public function __invoke(UpdateCategoryRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateCategoryCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('categories.show', ['company' => $company, 'id' => $id]);
    }
}
