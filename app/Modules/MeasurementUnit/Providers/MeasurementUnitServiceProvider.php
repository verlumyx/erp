<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Providers;

use App\Modules\MeasurementUnit\Repositories\Contracts\MeasurementUnitRepositoryInterface;
use App\Modules\MeasurementUnit\Repositories\MeasurementUnitRepository;
use Illuminate\Support\ServiceProvider;

class MeasurementUnitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            MeasurementUnitRepositoryInterface::class,
            MeasurementUnitRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
