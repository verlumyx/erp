<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Providers;

use App\Modules\SalesReturn\Repositories\Contracts\SalesReturnRepositoryInterface;
use App\Modules\SalesReturn\Repositories\SalesReturnRepository;
use Illuminate\Support\ServiceProvider;

class SalesReturnServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SalesReturnRepositoryInterface::class,
            SalesReturnRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
