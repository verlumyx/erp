<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Entry\Commands\SearchEntryCommand;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use App\Modules\Import\Repositories\Contracts\ImportRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Las recepciones que un expediente puede costear.
 *
 * Es la búsqueda de entradas del select remoto acotada por lo que el módulo
 * exige: confirmadas —un borrador todavía no valoró nada—, de la bodega del
 * expediente, y sin otro expediente vivo detrás. Costear dos veces la misma
 * entrada repartiría el mismo gasto dos veces sobre la misma mercancía.
 *
 * Hidratar lo ya elegido no filtra nada de eso: una entrada que este mismo
 * expediente tomó sigue siendo la suya.
 */
class ImportEntryOptionSearchService
{
    public function __construct(
        private readonly EntryRepositoryInterface $entries,
        private readonly ImportRepositoryInterface $imports,
    ) {}

    /**
     * @return array{ data: array<int, Entry>, total: int }
     */
    public function execute(SearchEntryCommand $command, ?string $exceptImportId = null): array
    {
        $result = $this->entries->search($command);

        (new Collection($result['data']))->loadMissing(['supplier', 'warehouse']);

        if (($command->filters['ids'] ?? '') !== '') {
            return $result;
        }

        $taken = $this->imports->entriesTakenElsewhere(
            $command->companyId,
            array_map(static fn (Entry $entry): string => $entry->id, $result['data']),
            $exceptImportId,
        );

        $data = array_values(array_filter(
            $result['data'],
            static fn (Entry $entry): bool => ! in_array($entry->id, $taken, true),
        ));

        return ['data' => $data, 'total' => $result['total'] - (count($result['data']) - count($data))];
    }
}
