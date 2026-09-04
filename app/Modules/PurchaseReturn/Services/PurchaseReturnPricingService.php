<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Services;

use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use App\Modules\PurchaseReturn\Commands\PurchaseReturnLineData;

/**
 * A qué precio se acredita cada línea devuelta.
 *
 * La pantalla de la devolución no pregunta el costo, ni el impuesto, ni el
 * descuento: eso se pactó al comprar. Una línea atada a una línea de la factura
 * copia lo que la factura cobró —es lo que la nota de crédito devolverá—; una
 * línea suelta se valora al costo promedio del artículo, que es lo único que el
 * sistema sabe de ella.
 *
 * El promedio está en **unidad base** y `unit_price` es por unidad de la línea,
 * así que hay que multiplicar por el factor de conversión: devolver una caja de
 * doce vale doce veces lo que vale la unidad.
 */
class PurchaseReturnPricingService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $invoices,
        private readonly ItemRepositoryInterface $items,
    ) {}

    /**
     * Las mismas líneas, ya valoradas.
     *
     * @param  array<int, PurchaseReturnLineData>  $lines
     * @return array<int, PurchaseReturnLineData>
     */
    public function apply(?string $companyId, ?string $invoiceId, array $lines): array
    {
        $invoiceLines = $this->invoiceLines($invoiceId, $companyId);
        $items = $this->itemsOf($companyId, $lines);

        $priced = [];

        foreach ($lines as $index => $line) {
            $source = filled($line->purchaseInvoiceLineId)
                ? ($invoiceLines[$line->purchaseInvoiceLineId] ?? null)
                : null;

            $priced[$index] = $source instanceof PurchaseInvoiceLine
                ? $this->fromInvoiceLine($line, $source)
                : $this->fromAverageCost($line, $items[$line->itemId] ?? null);
        }

        return $priced;
    }

    /** Lo que la factura cobró: precio, impuesto, retención y descuento. */
    private function fromInvoiceLine(
        PurchaseReturnLineData $line,
        PurchaseInvoiceLine $source,
    ): PurchaseReturnLineData {
        return $line->withPricing(
            unitPrice: round((float) $source->unit_price, 6),
            taxId: $source->tax_id,
            taxPercent: (float) $source->tax_percent,
            withholdingPercent: (float) $source->withholding_percent,
            discountPercent: (float) $source->discount_percent,
        );
    }

    /**
     * Sin línea de factura no hay nada pactado: la mercancía vale lo que ya
     * vale en el inventario, y no lleva impuesto ni descuento porque no hay
     * factura detrás que los cobrara.
     */
    private function fromAverageCost(PurchaseReturnLineData $line, ?Item $item): PurchaseReturnLineData
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
     * Líneas de la factura de origen indexadas por id. Sin factura no hay nada
     * que indexar y todas las líneas caen al promedio.
     *
     * @return array<string, PurchaseInvoiceLine>
     */
    private function invoiceLines(?string $invoiceId, ?string $companyId): array
    {
        if (blank($invoiceId)) {
            return [];
        }

        $invoice = $this->invoices->findById((string) $invoiceId, $companyId);

        if (! $invoice instanceof PurchaseInvoice) {
            return [];
        }

        return $invoice->lines->keyBy('id')->all();
    }

    /**
     * Los artículos de las líneas, resueltos por el repositorio de su módulo:
     * la devolución nunca consulta las tablas del inventario directamente.
     *
     * @param  array<int, PurchaseReturnLineData>  $lines
     * @return array<string, Item>
     */
    private function itemsOf(?string $companyId, array $lines): array
    {
        $items = [];

        $itemIds = array_unique(array_map(
            static fn (PurchaseReturnLineData $line): string => $line->itemId,
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
