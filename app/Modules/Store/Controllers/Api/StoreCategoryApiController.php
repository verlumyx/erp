<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Store\Controllers\Api\Concerns\ReadsStoreContext;
use App\Modules\Store\Repositories\Contracts\StoreItemRepositoryInterface;
use App\Modules\Store\Resources\Api\StoreCategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Categorías activas que tengan al menos una publicación visible, con el
 * conteo de publicaciones.
 */
class StoreCategoryApiController extends Controller
{
    use ReadsStoreContext;

    public function __construct(
        private readonly StoreItemRepositoryInterface $repository,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $settings = $this->settings($request);

        return $this->cached([
            'data' => array_map(
                fn (array $category): array => (new StoreCategoryResource($category))->resolve(),
                $this->repository->visibleCategories((string) $settings->company_id),
            ),
        ]);
    }
}
