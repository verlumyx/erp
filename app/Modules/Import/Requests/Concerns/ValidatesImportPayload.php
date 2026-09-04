<?php

declare(strict_types=1);

namespace App\Modules\Import\Requests\Concerns;

use App\Modules\Import\Models\Import;
use App\Modules\Import\Models\ImportCost;
use App\Modules\Currency\Rules\ActiveCurrency;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Lo que comparten crear y actualizar un expediente.
 *
 * De los ítems solo se valida el estado: no se capturan, se derivan de las
 * recepciones. Un `allocated_amount` o un `new_unit_cost` enviados desde el
 * cliente ni siquiera llegan al comando.
 */
trait ValidatesImportPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function importRules(): array
    {
        return [
            'warehouse_id' => ['required', 'uuid', 'exists:app_warehouses,id'],
            'import_date' => ['required', 'date'],
            'arrival_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:60'],
            'allocation_method' => ['required', 'string', Rule::in(Import::ALLOCATION_METHODS)],

            'currency' => ['required', 'string', new ActiveCurrency],
            /** Opcional: sin valor la resuelve el sistema con el catálogo de tasas. */
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],

            'notes' => ['nullable', 'string'],

            'costs' => ['sometimes', 'array'],
            'costs.*.id' => ['nullable', 'uuid'],
            'costs.*.concept' => ['required', 'string', Rule::in(ImportCost::CONCEPTS)],
            'costs.*.description' => ['nullable', 'string', 'max:500'],
            'costs.*.sourceable_type' => ['nullable', 'string', Rule::in(ImportCost::SOURCE_TYPES)],
            'costs.*.sourceable_id' => ['nullable', 'uuid'],
            'costs.*.supplier_id' => ['nullable', 'uuid', 'exists:app_suppliers,id'],
            'costs.*.currency' => ['required', 'string', new ActiveCurrency],
            'costs.*.amount' => ['required', 'numeric', 'min:0'],
            'costs.*.notes' => ['nullable', 'string', 'max:500'],
            'costs.*.status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],

            'entries' => ['sometimes', 'array'],
            'entries.*.id' => ['nullable', 'uuid'],
            'entries.*.entry_id' => ['required', 'uuid', 'exists:app_entries,id'],
            'entries.*.status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],

            /** De un ítem solo se admite su estado: el resto lo pone el backend. */
            'lines' => ['sometimes', 'array'],
            'lines.*.entry_line_id' => ['required', 'uuid'],
            'lines.*.status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function importMessages(): array
    {
        return [
            'warehouse_id.required' => 'La bodega es obligatoria: el expediente revaloriza una sola.',
            'warehouse_id.exists' => 'La bodega indicada no existe.',
            'import_date.required' => 'La fecha del expediente es obligatoria.',
            'allocation_method.in' => 'Ese método de reparto no existe.',
            'currency.required' => 'La moneda del expediente es obligatoria.',
            'costs.*.concept.required' => 'Indica por qué se cobró.',
            'costs.*.currency.required' => 'Indica la moneda en la que se cobró.',
            'costs.*.amount.required' => 'Indica el importe del cobro.',
            'entries.*.entry_id.required' => 'Elige la recepción que se costea.',
            'entries.*.entry_id.exists' => 'La recepción indicada no existe.',
        ];
    }

    /**
     * Lo que no cabe en una regla de campo: un concepto libre exige decir cuál,
     * y una recepción no se elige dos veces en el mismo expediente.
     */
    protected function validateImportInvariants(Validator $validator): void
    {
        foreach ((array) $this->input('costs', []) as $index => $cost) {
            if (! is_array($cost) || ($cost['status'] ?? 'active') !== 'active') {
                continue;
            }

            if (($cost['concept'] ?? '') === ImportCost::FREE_CONCEPT
                && trim((string) ($cost['description'] ?? '')) === ''
            ) {
                $validator->errors()->add(
                    "costs.{$index}.description",
                    'Un cobro de concepto «otro» tiene que decir cuál.',
                );
            }
        }

        $seen = [];

        foreach ((array) $this->input('entries', []) as $index => $entry) {
            if (! is_array($entry) || ($entry['status'] ?? 'active') !== 'active') {
                continue;
            }

            $entryId = (string) ($entry['entry_id'] ?? '');

            if ($entryId === '') {
                continue;
            }

            if (isset($seen[$entryId])) {
                $validator->errors()->add(
                    "entries.{$index}.entry_id",
                    'Esa recepción ya está en el expediente.',
                );
            }

            $seen[$entryId] = true;
        }
    }
}
