<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Providers;

use App\Modules\SupplierAdvance\Repositories\Contracts\SupplierAdvanceRepositoryInterface;
use App\Modules\SupplierAdvance\Repositories\SupplierAdvanceRepository;
use Illuminate\Support\ServiceProvider;

class SupplierAdvanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SupplierAdvanceRepositoryInterface::class,
            SupplierAdvanceRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
