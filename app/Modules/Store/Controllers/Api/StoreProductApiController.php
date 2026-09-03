<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Store\Commands\SearchStoreItemCommand;
use App\Modules\Store\Controllers\Api\Concerns\ReadsStoreContext;
use App\Modules\Store\Models\StoreItem;
use App\Modules\Store\Repositories\Contracts\StoreItemRepositoryInterface;
use App\Modules\Store\Resources\Api\StoreProductResource;
use App\Modules\Store\Services\StoreCatalogService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catálogo público: listado paginado y detalle por `slug`. Solo lo visible
 * (publicación activa + artículo activo y vendible). Con token de comprador
 * vinculado, los precios salen de la lista de su cliente.
 */
class StoreProductApiController extends Controller
{
    use ReadsStoreContext;

    private const PER_PAGE = 24;

    private const MAX_PER_PAGE = 60;

    private const SORTS = ['order', 'name', 'price', 'newest'];

    public function __construct(
        private readonly StoreItemRepositoryInterface $repository,
        private readonly StoreCatalogService $catalog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $settings = $this->settings($request);
        $customer = $this->customer($request);

        $perPage = min(max($request->integer('per_page', self::PER_PAGE), 1), self::MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $sort = $request->string('sort', 'order')->toString();

        $command = new SearchStoreItemCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'category_id' => $request->string('category')->toString(),
                'is_featured' => $request->string('featured')->toString() === 'yes' ? 'yes' : '',
            ],
            limit: $perPage,
            offset: ($page - 1) * $perPage,
            companyId: (string) $settings->company_id,
            sort: in_array($sort, self::SORTS, true) ? $sort : 'order',
        );

        $result = $this->repository->searchVisible($command, $this->catalog->priceListFor($settings, $customer));

        $products = $this->catalog->products($settings, new Collection($result['data']), $customer);

        return $this->cached([
            'data' => array_map(
                fn (array $product): array => (new StoreProductResource($product))->resolve(),
                $products,
            ),
            'meta' => [
                'total' => $result['total'],
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => max(1, (int) ceil($result['total'] / $perPage)),
            ],
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $settings = $this->settings($request);

        $storeItem = $this->repository->findVisibleBySlug((string) $settings->company_id, $slug);

        if (! $storeItem instanceof StoreItem) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        return $this->cached([
            'data' => (new StoreProductResource(
                $this->catalog->product($settings, $storeItem, $this->customer($request)),
            ))->resolve(),
        ]);
    }
}
