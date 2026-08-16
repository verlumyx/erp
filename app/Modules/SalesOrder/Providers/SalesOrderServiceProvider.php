<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Providers;

use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Modules\SalesOrder\Repositories\SalesOrderRepository;
use Illuminate\Support\ServiceProvider;

class SalesOrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SalesOrderRepositoryInterface::class,
            SalesOrderRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
