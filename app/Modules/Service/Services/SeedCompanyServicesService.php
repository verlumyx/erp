<?php

declare(strict_types=1);

namespace App\Modules\Service\Services;

use App\Modules\Service\Commands\CreateServiceCommand;
use App\Modules\Service\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Support\Str;

class SeedCompanyServicesService
{
    public function __construct(
        private readonly ServiceRepositoryInterface $repository,
    ) {}

    /**
     * Precarga el catálogo de servicios de streaming (config/streaming.php)
     * para una empresa recién creada. Cada servicio recibe su código
     * secuencial por empresa y su logo público.
     */
    public function execute(string $companyId): void
    {
        $logoPath = config('streaming.logo_path');

        /** @var array<int, array{name: string, slug: string, max_profiles: int}> $defaults */
        $defaults = config('streaming.default_services', []);

        foreach ($defaults as $service) {
            $this->repository->create(new CreateServiceCommand(
                id: Str::uuid7()->toString(),
                companyId: $companyId,
                name: $service['name'],
                maxProfiles: $service['max_profiles'],
                logoUrl: '/'.$logoPath.'/'.$service['slug'].'.svg',
            ));
        }
    }
}
