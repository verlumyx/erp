<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use App\Modules\Import\Commands\ImportCostingData;
use App\Modules\Import\Models\Import;
use App\Modules\Import\Repositories\Contracts\ImportRepositoryInterface;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Lo que el expediente no puede comprobar sin haber leído las entradas ni el
 * maestro de artículos.
 *
 * Solo se costean entradas confirmadas —un borrador todavía no valoró nada, y
 * no hay costo que corregir— y todas de la bodega del expediente, porque el
 * ajuste que genera lleva una sola bodega en su cabecera. Una entrada tampoco
 * puede estar en dos expedientes vivos: repartir el mismo gasto dos veces sobre
 * la misma mercancía la contaría doble.
 *
 * Vive aparte de los servicios de acción porque Crear y Actualizar la necesitan
 * igual: el expediente se edita en borrador y cada guardado vuelve a comprobar
 * lo mismo.
 */
class ImportLimitsService
{
    public function __construct(
        private readonly EntryRepositoryInterface $entries,
        private readonly ImportRepositoryInterface $imports,
        private readonly ItemRepositoryInterface $items,
    ) {}

    /**
     * @param  array<int, string>  $entryIds
     *
     * @throws ValidationException
     */
    public function guard(
        ?string $companyId,
        string $warehouseId,
        string $allocationMethod,
        array $entryIds,
        ImportCostingData $costing,
        ?string $exceptImportId = null,
    ): void {
        $this->guardEntries($companyId, $warehouseId, $entryIds, $exceptImportId);
        $this->guardDimensions($companyId, $allocationMethod, $costing);
    }

    /**
     * Lo que hace falta para mandar revalorizar: que quede gasto que
     * capitalizar. Sin él el ajuste nacería sin líneas, y un ajuste sin líneas
     * no revaloriza nada.
     *
     * @throws ValidationException
     */
    public function guardConfirmable(Import $import, ImportCostingData $costing): void
    {
        if ($costing->hasCapitalizableAmount()) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => 'No hay gasto que capitalizar: revisa los costos, las recepciones y lo que queda en existencia.',
        ]);
    }

    /**
     * @param  array<int, string>  $entryIds
     *
     * @throws ValidationException
     */
    private function guardEntries(
        ?string $companyId,
        string $warehouseId,
        array $entryIds,
        ?string $exceptImportId,
    ): void {
        $errors = [];
        $taken = $this->imports->entriesTakenElsewhere($companyId, $entryIds, $exceptImportId);

        foreach ($entryIds as $index => $entryId) {
            $entry = $this->entries->findById($entryId, $companyId);

            if (! $entry instanceof Entry) {
                $errors["entries.{$index}.entry_id"] = 'Esa recepción no existe en la empresa.';

                continue;
            }

            if (! in_array($entry->status, Entry::POSTED_STATUSES, true)) {
                $errors["entries.{$index}.entry_id"] = "La entrada {$entry->code} no está confirmada: todavía no valoró nada.";

                continue;
            }

            if ($entry->warehouse_id !== $warehouseId) {
                $errors["entries.{$index}.entry_id"] = "La entrada {$entry->code} no llegó a la bodega del expediente.";

                continue;
            }

            if (in_array($entryId, $taken, true)) {
                $errors["entries.{$index}.entry_id"] = "La entrada {$entry->code} ya se está costeando en otro expediente.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Repartir por peso o por volumen exige que el artículo los traiga
     * registrados: un dato en cero deja el expediente sin guardar, porque
     * repartir por un peso que nadie cargó da un costo inventado.
     *
     * @throws ValidationException
     */
    private function guardDimensions(?string $companyId, string $allocationMethod, ImportCostingData $costing): void
    {
        if (! in_array($allocationMethod, Import::DIMENSIONAL_METHODS, true)) {
            return;
        }

        $missing = [];

        foreach ($costing->lines as $line) {
            if ($line['status'] !== 'active' || $line['allocation_base'] > 0.0) {
                continue;
            }

            $item = $this->items->findById($line['item_id'], $companyId);

            $missing[$line['item_id']] = $item instanceof Item
                ? ($item->code ?? $item->name)
                : $line['item_id'];
        }

        if ($missing === []) {
            return;
        }

        $label = $allocationMethod === 'weight' ? 'peso' : 'volumen';

        throw ValidationException::withMessages([
            'allocation_method' => "Estos artículos no tienen {$label} registrado: ".implode(', ', $missing).'.',
        ]);
    }
}
