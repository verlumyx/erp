<?php

declare(strict_types=1);

namespace App\Modules\Item\Requests\Concerns;

use App\Modules\Currency\Rules\ActiveCurrency;
use App\Modules\Item\Models\Item;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: los campos propios del artículo más
 * sus dos tablas de detalle (unidades y precios por lista).
 */
trait ValidatesItemPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function itemRules(): array
    {
        $companyId = session('current_company_id');

        return [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in(Item::TYPES)],
            'category_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_categories', 'id')->where('company_id', $companyId),
            ],
            'cost_method' => ['required', 'string', Rule::in(Item::COST_METHODS)],
            'standard_cost' => ['nullable', 'numeric', 'min:0'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'is_purchasable' => ['required', 'string', 'in:yes,no'],
            'is_sellable' => ['required', 'string', 'in:yes,no'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'volume' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],

            'units' => ['required', 'array', 'min:1'],
            'units.*.measurement_unit_id' => [
                'required',
                'uuid',
                'distinct',
                Rule::exists('app_measurement_units', 'id')->where('company_id', $companyId),
            ],
            'units.*.is_base' => ['required', 'string', 'in:yes,no'],
            'units.*.conversion_factor' => ['required', 'numeric', 'gt:0'],
            'units.*.status' => ['nullable', 'string', 'in:active,inactive'],

            'prices' => ['nullable', 'array'],
            'prices.*.price_list_id' => [
                'required',
                'uuid',
                Rule::exists('app_price_lists', 'id')->where('company_id', $companyId),
            ],
            'prices.*.price' => ['required', 'numeric', 'min:0'],
            'prices.*.currency' => ['required', 'string', new ActiveCurrency],
            'prices.*.status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function itemMessages(): array
    {
        return [
            'sku.required' => 'El SKU del artículo es obligatorio.',
            'sku.unique' => 'Ya existe un artículo con ese SKU en la empresa.',
            'barcode.unique' => 'Ya existe un artículo con ese código de barras en la empresa.',
            'name.required' => 'El nombre del artículo es obligatorio.',
            'type.required' => 'El tipo de artículo es obligatorio.',
            'cost_method.required' => 'El método de costo es obligatorio.',
            'units.required' => 'El artículo debe tener al menos una unidad de medida.',
            'units.min' => 'El artículo debe tener al menos una unidad de medida.',
            'units.*.measurement_unit_id.required' => 'Selecciona la unidad de medida.',
            'units.*.measurement_unit_id.distinct' => 'No se puede repetir la misma unidad de medida.',
            'units.*.conversion_factor.gt' => 'El factor de conversión debe ser mayor que cero.',
            'prices.*.price_list_id.required' => 'Selecciona la lista de precio.',
            'prices.*.currency.required' => 'La moneda del precio es obligatoria.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validateItemInvariants(Validator $validator): void
    {
        $units = $this->input('units', []);
        $baseIndexes = [];

        foreach ($units as $index => $unit) {
            if (($unit['is_base'] ?? 'no') === 'yes') {
                $baseIndexes[] = $index;
            }
        }

        if (count($baseIndexes) === 0) {
            $validator->errors()->add('units', 'Debes marcar exactamente una unidad como unidad base.');
        }

        if (count($baseIndexes) > 1) {
            $validator->errors()->add('units', 'Solo una unidad puede ser la unidad base del artículo.');
        }

        $minPrice = (float) $this->input('min_price', 0);
        $seen = [];

        foreach ($this->input('prices', []) as $index => $price) {
            $priceListId = $price['price_list_id'] ?? '';

            if (in_array($priceListId, $seen, true)) {
                $validator->errors()->add(
                    "prices.{$index}.price_list_id",
                    'Ya hay un precio para esa lista de precio.',
                );
            }

            $seen[] = $priceListId;

            if (isset($price['price']) && (float) $price['price'] < $minPrice) {
                $validator->errors()->add(
                    "prices.{$index}.price",
                    'El precio no puede ser menor que el precio mínimo del artículo.',
                );
            }
        }
    }
}
