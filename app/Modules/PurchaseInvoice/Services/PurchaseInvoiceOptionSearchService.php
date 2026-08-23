<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Services;

use App\Modules\PurchaseInvoice\Commands\SearchPurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * La búsqueda que alimenta `purchase-invoices.lookup`, más las relaciones que
 * el `meta` de cada opción necesita.
 *
 * El `loadMissing` vive aquí y no en el repositorio para que el listado del
 * módulo no pague el costo de cargar las líneas de cada factura: solo el select
 * remoto las necesita, porque de ellas salen las líneas que una nota de crédito
 * acredita.
 */
class PurchaseInvoiceOptionSearchService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: PurchaseInvoice[], total: int }
     */
    public function execute(SearchPurchaseInvoiceCommand $command): array
    {
        $result = $this->repository->search($command);

        (new Collection($result['data']))
            ->loadMissing(['lines' => fn ($q) => $q->where('status', 'active')->orderBy('line_number')])
            ->loadMissing(['lines.item', 'lines.measurementUnit']);

        return $result;
    }
}
