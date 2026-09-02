<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Services;

use App\Modules\Dispatch\Commands\DispatchLineData;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;

/**
 * A qué precio sale cada línea del despacho.
 *
 * La pantalla del despacho no pregunta el precio, ni el impuesto, ni el
 * descuento: eso se decidió al vender. Una línea que despacha una línea del
 * pedido copia lo que el pedido pactó; una línea sin pedido se valora al costo
 * promedio del artículo, que es lo único que el sistema sabe de ella.
 *
 * Los importes del despacho son informativos —la guía no factura—, pero tienen
 * que enseñar lo mismo que el pedido.
 *
 * El promedio está en **unidad base** y `unit_price` es por unidad de la línea,
 * así que hay que multiplicar por el factor de conversión.
 *
 * No se confunde con `DispatchCostService`: aquel resuelve `unit_cost`, el
 * costo con el que el kardex descarga la mercancía. Son dos columnas y dos
 * conceptos.
 */
class DispatchPricingService
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $orders,
        private readonly ItemRepositoryInterface $items,
    ) {}

    /**
     * Las mismas líneas, ya valoradas.
     *
     * @param  array<int, DispatchLineData>  $lines
     * @return array<int, DispatchLineData>
     */
    public function apply(?string $companyId, ?string $sourceableId, array $lines): array
    {
        $orderLines = $this->orderLines($companyId, $sourceableId);
        $items = $this->itemsOf($companyId, $lines);

        $priced = [];

        foreach ($lines as $index => $line) {
            $source = $orderLines[$line->sourceableId] ?? null;

            $priced[$index] = $source instanceof SalesOrderLine
                ? $this->fromOrderLine($line, $source)
                : $this->fromAverageCost($line, $items[$line->itemId] ?? null);
        }

        return $priced;
    }

    /** Lo que el pedido pactó: precio, impuesto, retención y descuento. */
    private function fromOrderLine(DispatchLineData $line, SalesOrderLine $source): DispatchLineData
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
     * Sin pedido no hay nada pactado: la mercancía vale lo que ya vale en el
     * inventario, y no lleva impuesto ni descuento porque no hay factura detrás.
     */
    private function fromAverageCost(DispatchLineData $line, ?Item $item): DispatchLineData
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
     * Las líneas del pedido origen, indexadas por id. Un despacho suelto no
     * tiene ninguna.
     *
     * @return array<string, SalesOrderLine>
     */
    private function orderLines(?string $companyId, ?string $sourceableId): array
    {
        if (blank($sourceableId)) {
            return [];
        }

        $order = $this->orders->findById((string) $sourceableId, $companyId);

        if (! $order instanceof SalesOrder) {
            return [];
        }

        $lines = [];

        foreach ($order->lines as $line) {
            /** @var SalesOrderLine $line */
            $lines[$line->id] = $line;
        }

        return $lines;
    }

    /**
     * Los artículos de las líneas, resueltos por el repositorio de su módulo:
     * el despacho nunca consulta las tablas del inventario directamente.
     *
     * @param  array<int, DispatchLineData>  $lines
     * @return array<string, Item>
     */
    private function itemsOf(?string $companyId, array $lines): array
    {
        $items = [];

        $itemIds = array_unique(array_map(
            static fn (DispatchLineData $line): string => $line->itemId,
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
