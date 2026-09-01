<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Services;

use App\Modules\SalesInvoice\Commands\SearchSalesInvoiceCommand;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * La búsqueda que alimenta `sales-invoices.lookup`, más las relaciones que el
 * `meta` de cada opción necesita.
 *
 * El `loadMissing` vive aquí y no en el repositorio para que el listado del
 * módulo no pague el costo de cargar las líneas de cada factura: solo el select
 * remoto las necesita, porque de ellas salen las líneas que una nota de crédito
 * acredita.
 */
class SalesInvoiceOptionSearchService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: SalesInvoice[], total: int }
     */
    public function execute(SearchSalesInvoiceCommand $command): array
    {
        $result = $this->repository->search($command);

        (new Collection($result['data']))
            ->loadMissing(['lines' => fn ($q) => $q->where('status', 'active')->orderBy('line_number')])
            ->loadMissing(['lines.item', 'lines.measurementUnit']);

        return $result;
    }
}
