<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Providers;

use App\Modules\SupplierType\Repositories\Contracts\SupplierTypeRepositoryInterface;
use App\Modules\SupplierType\Repositories\SupplierTypeRepository;
use Illuminate\Support\ServiceProvider;

class SupplierTypeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SupplierTypeRepositoryInterface::class,
            SupplierTypeRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
