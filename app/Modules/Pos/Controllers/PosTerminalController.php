<?php

declare(strict_types=1);

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Category\Models\Category;
use App\Modules\Client\Models\Client;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\Item\Models\Item;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\Pos\Repositories\Contracts\PosSessionRepositoryInterface;
use App\Modules\Pos\Resources\PosSaleResource;
use App\Modules\Pos\Resources\PosSessionResource;
use App\Modules\Pos\Services\PosSaleHoldService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosTerminalController extends Controller
{
    public function __construct(
        private readonly PosSessionRepositoryInterface $sessionRepository,
        private readonly PosSaleHoldService $holdService,
        private readonly DocumentRatesResolverInterface $rates,
    ) {}

    public function __invoke(Request $request, string $company): Response|RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('pos.access') ?? false, 403);

        $companyId = session('current_company_id');
        $user = $request->user();

        // 1. Verify user has an open session
        $session = $this->sessionRepository->findActiveForUser($user->id, $companyId);

        if (! $session) {
            return redirect()
                ->route('pos-sessions.open', ['company' => $company])
                ->with('warning', 'Debes abrir un turno de caja para operar el POS.');
        }

        $warehouseId = $session->register?->warehouse_id;
        $priceListId = $session->register?->price_list_id;

        // 2. Fetch categories
        $categories = Category::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Stock map by item_id in register's warehouse
        $stockMap = $warehouseId
            ? ItemStock::query()
                ->where('company_id', $companyId)
                ->where('warehouse_id', $warehouseId)
                ->where('status', 'active')
                ->groupBy('item_id')
                ->selectRaw('item_id, COALESCE(SUM(available_quantity), 0) as total_stock')
                ->pluck('total_stock', 'item_id')
            : collect();

        // 3. Fetch items sellable
        $itemsQuery = Item::query()
            ->with(['units.measurementUnit', 'prices', 'saleTax'])
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->where('is_sellable', 'yes');

        $items = $itemsQuery->get()->map(function (Item $item) use ($priceListId, $stockMap) {
            $stock = $item->movesStock()
                ? (float) ($stockMap->get($item->id, 0.0))
                : 9999.0;

            // Get unit price according to price list or default
            $priceRow = $priceListId
                ? $item->prices->firstWhere('price_list_id', $priceListId)
                : $item->prices->first();

            $price = $priceRow ? (float) $priceRow->price : (float) $item->min_price;

            // Base unit
            $baseUnit = $item->units->firstWhere('is_base', 'yes') ?? $item->units->first();

            return [
                'id' => $item->id,
                'code' => $item->code,
                'sku' => $item->sku,
                'barcode' => $item->barcode,
                'name' => $item->name,
                'category_id' => $item->category_id,
                'unit_price' => $price,
                'stock' => $stock,
                'moves_stock' => $item->movesStock(),
                'measurement_unit_id' => $baseUnit?->measurement_unit_id,
                'measurement_unit_name' => $baseUnit?->measurementUnit?->name,
                'measurement_unit_symbol' => $baseUnit?->measurementUnit?->symbol,
                'tax_id' => $item->sale_tax_id,
                'tax_percent' => $item->saleTax ? (float) $item->saleTax->rate : 0.0,
            ];
        });

        // 4. Resolve exchange rates
        $rateData = $this->rates->forDocument($companyId, 'USD', now()->toDateString());
        $vesRate = (float) $rateData->exchangeRate;

        // 5. Fetch paused / held sales for this session
        $heldSales = $this->holdService->listHeld($session->id);

        // 6. Common clients for quick selection
        $clients = Client::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'document_type', 'document_number', 'phone', 'email']);

        return Inertia::render('pos/index', [
            'session' => (new PosSessionResource($session))->resolve(),
            'categories' => $categories,
            'items' => $items,
            'rate' => $vesRate,
            'heldSales' => PosSaleResource::collection($heldSales),
            'clients' => $clients,
        ]);
    }
}
