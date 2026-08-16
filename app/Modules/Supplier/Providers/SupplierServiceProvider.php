<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Providers;

use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use App\Modules\Supplier\Repositories\SupplierRepository;
use Illuminate\Support\ServiceProvider;

class SupplierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SupplierRepositoryInterface::class,
            SupplierRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
