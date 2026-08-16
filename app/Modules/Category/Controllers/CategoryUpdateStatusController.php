<?php

declare(strict_types=1);

namespace App\Modules\Category\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Category\Commands\UpdateStatusCategoryCommand;
use App\Modules\Category\Requests\UpdateStatusCategoryRequest;
use App\Modules\Category\Services\CategoryUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class CategoryUpdateStatusController extends Controller
{
    public function __construct(
        private readonly CategoryUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusCategoryRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusCategoryCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('categories.index', ['company' => $company])
            ->with('success', 'Estado de la categoría actualizado correctamente.');
    }
}
