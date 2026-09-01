<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Services;

use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Models\SalesInvoiceLine;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use App\Modules\SalesReturn\Commands\SalesReturnLineData;

/**
 * A qué costo vuelve cada línea al inventario.
 *
 * La mercancía reingresa **al costo con el que salió** —el `unit_cost` que la
 * factura congeló al confirmarse—, no al promedio vigente: si volviera al
 * promedio, devolver mercancía inventaría o destruiría margen sin que nadie
 * comprara ni vendiera nada.
 *
 * Sin línea de factura de origen no hay costo congelado que copiar, y se cae
 * al costo promedio del artículo, que es lo que el kardex usaría de todos modos.
 *
 * El costo no se captura en la pantalla: lo resuelve este servicio y lo escribe
 * el repositorio en `unit_cost`.
 */
class SalesReturnCostService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $invoices,
        private readonly ItemRepositoryInterface $items,
    ) {}

    /**
     * @param  array<int, SalesReturnLineData>  $lines
     * @return array<int, float> Costo unitario por línea, con la misma clave.
     */
    public function resolve(?string $invoiceId, ?string $companyId, array $lines): array
    {
        $invoiceLines = $this->invoiceLines($invoiceId, $companyId);
        $averages = $this->averageCosts($companyId, $lines);

        $costs = [];

        foreach ($lines as $index => $line) {
            $invoiceLine = filled($line->salesInvoiceLineId)
                ? ($invoiceLines[$line->salesInvoiceLineId] ?? null)
                : null;

            $costs[$index] = $invoiceLine instanceof SalesInvoiceLine
                ? round((float) $invoiceLine->unit_cost, 6)
                : round($averages[$line->itemId] ?? 0.0, 6);
        }

        return $costs;
    }

    /**
     * Líneas de la factura de origen indexadas por id. Sin factura no hay nada
     * que indexar y todas las líneas caen al promedio.
     *
     * @return array<string, SalesInvoiceLine>
     */
    private function invoiceLines(?string $invoiceId, ?string $companyId): array
    {
        if (blank($invoiceId)) {
            return [];
        }

        $invoice = $this->invoices->findById($invoiceId, $companyId);

        if (! $invoice instanceof SalesInvoice) {
            return [];
        }

        return $invoice->lines->keyBy('id')->all();
    }

    /**
     * Costo promedio de cada artículo de la devolución, resuelto a través del
     * repositorio de artículos: este módulo nunca consulta las tablas del
     * módulo de inventario directamente.
     *
     * @param  array<int, SalesReturnLineData>  $lines
     * @return array<string, float>
     */
    private function averageCosts(?string $companyId, array $lines): array
    {
        $averages = [];

        $itemIds = array_unique(array_map(
            static fn (SalesReturnLineData $line): string => $line->itemId,
            $lines,
        ));

        foreach ($itemIds as $itemId) {
            $item = $this->items->findById($itemId, $companyId);

            if ($item instanceof Item) {
                $averages[$itemId] = (float) $item->average_cost;
            }
        }

        return $averages;
    }
}
