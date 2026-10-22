<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Services;

use App\Modules\MeasurementUnit\Commands\CreateMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\MeasurementUnit\Repositories\Contracts\MeasurementUnitRepositoryInterface;
use Illuminate\Support\Str;

class MeasurementUnitProvisionService
{
    /**
     * Standard basic measurement units for a company:
     * Unidad (UND), Servicio (SERV), Kilogramo (KG), Litro (LT), Caja (CJ).
     *
     * @var array<int, array{name: string, abbreviation: string, description: string}>
     */
    public const DEFAULT_UNITS = [
        [
            'name' => 'Unidad',
            'abbreviation' => 'UND',
            'description' => 'Unidad estándar de conteo de artículos',
        ],
        [
            'name' => 'Servicio',
            'abbreviation' => 'SERV',
            'description' => 'Servicios intangibles y mano de obra',
        ],
        [
            'name' => 'Kilogramo',
            'abbreviation' => 'KG',
            'description' => 'Unidad estándar de masa o peso',
        ],
        [
            'name' => 'Litro',
            'abbreviation' => 'LT',
            'description' => 'Unidad estándar de volumen y líquidos',
        ],
        [
            'name' => 'Caja',
            'abbreviation' => 'CJ',
            'description' => 'Presentación o empaque en caja cerrada',
        ],
    ];

    public function __construct(
        private readonly MeasurementUnitRepositoryInterface $repository,
    ) {}

    /**
     * Provision default measurement units for a company if they do not already exist.
     */
    public function provisionDefaults(string $companyId, ?string $userId = null): void
    {
        foreach (self::DEFAULT_UNITS as $def) {
            $exists = MeasurementUnit::query()
                ->where('company_id', $companyId)
                ->where(function ($query) use ($def) {
                    $query->whereRaw('LOWER(name) = ?', [strtolower($def['name'])])
                        ->orWhereRaw('LOWER(abbreviation) = ?', [strtolower($def['abbreviation'])]);
                })
                ->exists();

            if ($exists) {
                continue;
            }

            $this->repository->create(new CreateMeasurementUnitCommand(
                id: Str::uuid7()->toString(),
                companyId: $companyId,
                name: $def['name'],
                abbreviation: $def['abbreviation'],
                createdBy: $userId,
                description: $def['description'],
            ));
        }
    }
}
