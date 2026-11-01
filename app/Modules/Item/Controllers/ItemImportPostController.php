<?php

declare(strict_types=1);

namespace App\Modules\Item\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Item\Requests\ImportItemRequest;
use App\Modules\Item\Services\ItemBulkImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ItemImportPostController extends Controller
{
    public function __construct(
        private readonly ItemBulkImportService $bulkImportService,
    ) {}

    public function __invoke(ImportItemRequest $request, string $company): JsonResponse|RedirectResponse
    {
        $file = $request->file('file');
        $userId = (string) $request->user()?->id;

        $result = $this->bulkImportService->execute($company, $file, $userId);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return back()->with('import_result', $result);
    }
}
