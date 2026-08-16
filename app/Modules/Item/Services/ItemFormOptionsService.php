<?php

declare(strict_types=1);

namespace App\Modules\Item\Services;

use App\Modules\Category\Commands\SearchCategoryCommand;
use App\Modules\Category\Models\Category;
use App\Modules\Category\Repositories\Contracts\CategoryRepositoryInterface;
use App\Modules\MeasurementUnit\Commands\SearchMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\MeasurementUnit\Repositories\Contracts\MeasurementUnitRepositoryInterface;
use App\Modules\PriceList\Commands\SearchPriceListCommand;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\PriceList\Repositories\Contracts\PriceListRepositoryInterface;
use App\Modules\Tax\Commands\SearchTaxCommand;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;

/**
 * Catálogos que alimentan los selects del formulario de artículo.
 *
 * Se resuelven a través de los repositorios de sus módulos: el módulo de
 * artículos nunca consulta sus tablas directamente.
 */
class ItemFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly MeasurementUnitRepositoryInterface $measurementUnits,
        private readonly PriceListRepositoryInterface $priceLists,
        private readonly TaxRepositoryInterface $taxes,
    ) {}

    /**
     * @return array{
     *     categories: array<int, array{id: string, name: string}>,
     *     measurementUnits: array<int, array{id: string, name: string, abbreviation: string}>,
     *     priceLists: array<int, array{id: string, name: string}>,
     *     taxes: array<int, array{id: string, name: string, percentage: string}>
     * }
     */
    public function execute(?string $companyId): array
    {
        $categories = $this->categories->search(new SearchCategoryCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        $measurementUnits = $this->measurementUnits->search(new SearchMeasurementUnitCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        $priceLists = $this->priceLists->search(new SearchPriceListCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        $taxes = $this->taxes->search(new SearchTaxCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        return [
            'categories' => array_map(
                fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
                $categories['data'],
            ),
            'measurementUnits' => array_map(
                fn (MeasurementUnit $unit): array => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'abbreviation' => $unit->abbreviation,
                ],
                $measurementUnits['data'],
            ),
            'priceLists' => array_map(
                fn (PriceList $priceList): array => [
                    'id' => $priceList->id,
                    'name' => $priceList->name,
                ],
                $priceLists['data'],
            ),
            'taxes' => array_map(
                fn (Tax $tax): array => [
                    'id' => $tax->id,
                    'name' => $tax->name,
                    'percentage' => (string) $tax->percentage,
                ],
                $taxes['data'],
            ),
        ];
    }
}
