<?php

declare(strict_types=1);

namespace App\Modules\Entry\Services;

use App\Modules\Dispatch\Models\DispatchLine;
use App\Modules\Entry\Commands\EntryLineData;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;

/**
 * A qué costo entra cada línea de la entrada.
 *
 * La pantalla de la entrada no pregunta el costo, ni el impuesto, ni el
 * descuento: eso se decidió al comprar. Una línea que recibe una línea de la
 * orden copia lo que la orden pactó; una línea sin orden —producción, donación,
 * inventario inicial— se valora al costo promedio del artículo.
 *
 * El promedio está en **unidad base** y `unit_price` es por unidad de la línea,
 * así que hay que multiplicar por el factor de conversión: recibir una caja de
 * doce cuesta doce veces lo que cuesta la unidad.
 *
 * Un artículo estrenado no tiene promedio todavía y entra a cero. Es el caso
 * del inventario inicial, y se corrige después con un ajuste de revaluación:
 * el costo no vuelve a la pantalla de la entrada solo por eso.
 */
class EntryPricingService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $orders,
        private readonly ItemRepositoryInterface $items,
    ) {}

    /**
     * Las mismas líneas, ya valoradas.
     *
     * @param  array<int, EntryLineData>  $lines
     * @return array<int, EntryLineData>
     */
    public function apply(
        ?string $companyId,
        ?string $sourceableType,
        ?string $sourceableId,
        array $lines,
    ): array {
        $orderLines = $this->orderLines($companyId, $sourceableType, $sourceableId);
        $dispatchLines = $this->dispatchLines($lines);
        $items = $this->itemsOf($companyId, $lines);

        $priced = [];

        foreach ($lines as $index => $line) {
            $source = $orderLines[$line->sourceableId] ?? null;
            $shipped = $dispatchLines[$line->sourceableId] ?? null;

            $priced[$index] = match (true) {
                $source instanceof PurchaseOrderLine => $this->fromOrderLine($line, $source),
                $shipped instanceof DispatchLine => $this->fromDispatchLine($line, $shipped),
                default => $this->fromAverageCost($line, $items[$line->itemId] ?? null),
            };
        }

        return $priced;
    }

    /**
     * El costo con el que la mercancía salió de la bodega de origen. Es lo que
     * hace que trasladar no invente ni destruya valor: el destino la recibe al
     * costo del origen, no al suyo.
     *
     * El costo del despacho está en unidad base; el de la línea es por unidad
     * de la línea, así que hay que multiplicar por el factor de conversión.
     */
    private function fromDispatchLine(EntryLineData $line, DispatchLine $shipped): EntryLineData
    {
        $factor = (float) $shipped->quantity > 0
            ? (float) $shipped->base_quantity / (float) $shipped->quantity
            : 1.0;

        return $line->withPricing(
            unitPrice: round((float) $shipped->unit_cost * $factor, 6),
            taxId: null,
            taxPercent: 0.0,
            withholdingPercent: 0.0,
            discountPercent: 0.0,
        );
    }

    /**
     * Las líneas del despacho que esta entrada recibe, indexadas por id. Una
     * entrada que no viene de un despacho no tiene ninguna.
     *
     * @param  array<int, EntryLineData>  $lines
     * @return array<string, DispatchLine>
     */
    private function dispatchLines(array $lines): array
    {
        $ids = [];

        foreach ($lines as $line) {
            if ($line->sourceableType === DispatchLine::MORPH_ALIAS && filled($line->sourceableId)) {
                $ids[] = (string) $line->sourceableId;
            }
        }

        if ($ids === []) {
            return [];
        }

        return DispatchLine::query()
            ->whereIn('id', array_values(array_unique($ids)))
            ->get()
            ->keyBy('id')
            ->all();
    }

    /** Lo que la orden pactó: costo, impuesto, retención y descuento. */
    private function fromOrderLine(EntryLineData $line, PurchaseOrderLine $source): EntryLineData
    {
        return $line->withPricing(
            unitPrice: round((float) $source->unit_price, 6),
            taxId: $source->tax_id,
            taxPercent: (float) $source->tax_percent,
            withholdingPercent: (float) $source->withholding_percent,
            discountPercent: (float) $source->discount_percent,
        );
    }

    /**
     * Sin orden no hay nada pactado: la mercancía vale lo que ya vale en el
     * inventario, y no lleva impuesto ni descuento porque no hay factura detrás.
     */
    private function fromAverageCost(EntryLineData $line, ?Item $item): EntryLineData
    {
        $average = $item instanceof Item ? (float) $item->average_cost : 0.0;

        return $line->withPricing(
            unitPrice: round($average * $this->factorFor($item, $line->measurementUnitId), 6),
            taxId: null,
            taxPercent: 0.0,
            withholdingPercent: 0.0,
            discountPercent: 0.0,
        );
    }

    /**
     * Las líneas de la orden origen, indexadas por id. Una entrada sin orden
     * —suelta, o la que recibe un traslado— no tiene ninguna, y no se va a
     * buscar entre las órdenes un documento que no lo es.
     *
     * @return array<string, PurchaseOrderLine>
     */
    private function orderLines(?string $companyId, ?string $sourceableType, ?string $sourceableId): array
    {
        if (blank($sourceableId) || $sourceableType !== PurchaseOrder::MORPH_ALIAS) {
            return [];
        }

        $order = $this->orders->findById((string) $sourceableId, $companyId);

        if (! $order instanceof PurchaseOrder) {
            return [];
        }

        $lines = [];

        foreach ($order->lines as $line) {
            /** @var PurchaseOrderLine $line */
            $lines[$line->id] = $line;
        }

        return $lines;
    }

    /**
     * Los artículos de las líneas, resueltos por el repositorio de su módulo:
     * la entrada nunca consulta las tablas del inventario directamente.
     *
     * @param  array<int, EntryLineData>  $lines
     * @return array<string, Item>
     */
    private function itemsOf(?string $companyId, array $lines): array
    {
        $items = [];

        $itemIds = array_unique(array_map(
            static fn (EntryLineData $line): string => $line->itemId,
            $lines,
        ));

        foreach ($itemIds as $itemId) {
            $item = $this->items->findById($itemId, $companyId);

            if ($item instanceof Item) {
                $items[$itemId] = $item;
            }
        }

        return $items;
    }

    /**
     * Factor con el que la unidad de la línea se convierte a la unidad base.
     * Una unidad sin registrar cae en 1, que es lo que ya rechazó el Request.
     */
    private function factorFor(?Item $item, string $measurementUnitId): float
    {
        if (! $item instanceof Item) {
            return 1.0;
        }

        foreach ($item->units as $unit) {
            /** @var ItemUnit $unit */
            if ($unit->measurement_unit_id === $measurementUnitId) {
                return (float) $unit->conversion_factor;
            }
        }

        return 1.0;
    }
}
