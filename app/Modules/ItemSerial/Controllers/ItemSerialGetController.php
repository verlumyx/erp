<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Entry\Models\EntryLineSerial;
use App\Modules\Item\Models\Item;
use App\Modules\ItemSerial\Commands\SearchItemSerialCommand;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\ItemSerial\Resources\ItemSerialOptionResource;
use App\Modules\ItemSerial\Resources\ItemSerialResource;
use App\Modules\ItemSerial\Services\ItemSerialFindService;
use App\Modules\ItemSerial\Services\ItemSerialOptionSearchService;
use App\Modules\ItemSerial\Services\ItemSerialSearchService;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Services\WarehouseSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ItemSerialGetController extends Controller
{
    /** Tope de registros cargados para los selectores del formulario. */
    private const OPTIONS_LIMIT = 500;

    /** @var array<int, string> */
    private const FILTER_KEYS = ['code', 'serial_number', 'item_id', 'lot_id', 'warehouse_id', 'status'];

    /** Tamaño de página del select remoto. */
    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    public function __construct(
        private readonly ItemSerialSearchService $searchService,
        private readonly ItemSerialFindService $findService,
        private readonly ItemSerialOptionSearchService $optionSearchService,
        private readonly WarehouseSearchService $warehouseSearchService,
    ) {}

    /**
     * Series como opciones de un select remoto: el padrón es demasiado grande
     * para viajar entero en las props de la pantalla que las elige.
     *
     * Se llama `lookup` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('item-serials.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $ids = $request->string('ids')->toString();

        $command = new SearchItemSerialCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $ids,
                'item_id' => $request->string('item_id')->toString(),
                'lot_id' => $request->string('lot_id')->toString(),
                'warehouse_id' => $request->string('warehouse_id')->toString(),
                /*
                 * Buscar ofrece solo lo que sigue en la bodega; hidratar lo ya
                 * elegido no filtra por estado: una serie que después se vendió
                 * o se devolvió sigue estando en el documento que se edita.
                 */
                'status' => $ids === ''
                    ? $request->string('status', 'available')->toString()
                    : $request->string('status')->toString(),
            ],
            limit: $perPage,
            offset: ($page - 1) * $perPage,
            companyId: $company,
        );

        $result = $this->optionSearchService->execute($command);

        return response()->json([
            'data' => array_map(
                fn (ItemSerial $serial): array => (new ItemSerialOptionResource($serial))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
        ]);
    }

    /**
     * Devuelve la última serie registrada para un artículo en la empresa,
     * el siguiente número de secuencia libre y las series existentes
     * para asegurar que las series sean únicas por artículo.
     */
    public function lastSequence(Request $request, string $company): JsonResponse
    {
        $user = $request->user();
        $canAccess = $user !== null && (
            $user->hasPermission('item-serials.list')
            || $user->hasPermission('entries.create')
            || $user->hasPermission('entries.edit')
            || $user->hasPermission('entries.list')
        );
        abort_unless($canAccess, 403);

        $itemId = $request->string('item_id')->toString();
        $requestedPrefix = $request->has('prefix') ? $request->string('prefix')->toString() : null;

        if ($itemId === '') {
            return response()->json([
                'item_id' => '',
                'last_serial_number' => null,
                'prefix' => $requestedPrefix ?? 'SN-',
                'next_number' => 1,
                'pad_length' => 4,
                'suggested_serial' => ($requestedPrefix ?? 'SN-').'0001',
                'existing_serials' => [],
            ]);
        }

        $item = Item::query()
            ->where('company_id', $company)
            ->where('id', $itemId)
            ->first();

        // 1. Obtener todas las series existentes del artículo en la empresa
        $masterSerials = ItemSerial::query()
            ->where('company_id', $company)
            ->where('item_id', $itemId)
            ->pluck('serial_number')
            ->all();

        $draftSerials = EntryLineSerial::query()
            ->where('app_entry_line_serials.company_id', $company)
            ->where('app_entry_line_serials.status', 'active')
            ->whereNotNull('app_entry_line_serials.serial_number')
            ->join('app_entry_lines', 'app_entry_lines.id', '=', 'app_entry_line_serials.entry_line_id')
            ->where('app_entry_lines.item_id', $itemId)
            ->pluck('app_entry_line_serials.serial_number')
            ->all();

        $existingSerials = array_values(array_unique(array_filter(
            array_merge($masterSerials, $draftSerials),
            static fn ($s): bool => filled($s),
        )));

        // 2. Buscar la última serie generada/registrada para ese artículo
        $lastSerialObj = ItemSerial::query()
            ->where('company_id', $company)
            ->where('item_id', $itemId)
            ->orderByDesc('created_at')
            ->orderByDesc('code')
            ->first();

        $lastSerialNumber = $lastSerialObj?->serial_number;

        if ($lastSerialNumber === null && $draftSerials !== []) {
            $lastDraftObj = EntryLineSerial::query()
                ->where('app_entry_line_serials.company_id', $company)
                ->where('app_entry_line_serials.status', 'active')
                ->join('app_entry_lines', 'app_entry_lines.id', '=', 'app_entry_line_serials.entry_line_id')
                ->where('app_entry_lines.item_id', $itemId)
                ->orderByDesc('app_entry_line_serials.created_at')
                ->first();
            $lastSerialNumber = $lastDraftObj?->serial_number;
        }

        // 3. Determinar prefijo por defecto y longitud de relleno desde la última serie
        $lastPrefix = null;
        $lastNum = null;
        $padLength = 4;

        if ($lastSerialNumber !== null && preg_match('/^(.*?)(\d+)$/', $lastSerialNumber, $matches)) {
            $lastPrefix = $matches[1];
            $lastNum = (int) $matches[2];
            $padLength = max(4, strlen($matches[2]));
        }

        $defaultPrefix = $lastPrefix ?? ($item?->sku ? $item->sku.'-' : ($item?->code ? $item->code.'-' : 'SN-'));
        $activePrefix = $requestedPrefix !== null ? $requestedPrefix : $defaultPrefix;

        // 4. Calcular el número máximo existente con el prefijo activo para evitar duplicados
        $maxNum = 0;
        foreach ($existingSerials as $s) {
            if ($activePrefix === '' || str_starts_with($s, $activePrefix)) {
                $suffix = substr($s, strlen($activePrefix));
                if (preg_match('/^\d+$/', $suffix)) {
                    $num = (int) $suffix;
                    if ($num > $maxNum) {
                        $maxNum = $num;
                        $padLength = max($padLength, strlen($suffix));
                    }
                }
            }
        }

        $nextNumber = $maxNum > 0 ? $maxNum + 1 : ($lastNum !== null && $activePrefix === $lastPrefix ? $lastNum + 1 : 1);
        $suggestedSerial = $activePrefix.str_pad((string) $nextNumber, $padLength, '0', STR_PAD_LEFT);

        return response()->json([
            'item_id' => $itemId,
            'last_serial_number' => $lastSerialNumber,
            'prefix' => $activePrefix,
            'next_number' => $nextNumber,
            'pad_length' => $padLength,
            'suggested_serial' => $suggestedSerial,
            'existing_serials' => $existingSerials,
        ]);
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('item-serials.list') ?? false, 403);

        $command = new SearchItemSerialCommand(
            filters: $request->only(self::FILTER_KEYS),
            limit: $request->integer('limit', 10),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('item-serials/index', [
            'serials' => array_map(
                fn (ItemSerial $serial): array => (new ItemSerialResource($serial))->resolve(),
                $result['data'],
            ),
            'warehouses' => $this->warehouseOptions(),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([...self::FILTER_KEYS, 'limit', 'offset']),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('item-serials.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('item-serials/show', [
            'serial' => (new ItemSerialResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('item-serials.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('item-serials/edit', [
            'serial' => (new ItemSerialResource($model))->resolve(),
            'warehouses' => $this->warehouseOptions(),
        ]);
    }

    /**
     * Bodegas activas donde puede estar la unidad serializada.
     *
     * @return array<int, array{id: string, name: string}>
     */
    private function warehouseOptions(): array
    {
        $result = $this->warehouseSearchService->execute(new SearchWarehouseCommand(
            filters: ['status' => 'active'],
            limit: self::OPTIONS_LIMIT,
            offset: 0,
            companyId: session('current_company_id'),
        ));

        return array_map(
            fn (Warehouse $warehouse): array => ['id' => $warehouse->id, 'name' => $warehouse->name],
            $result['data'],
        );
    }
}
