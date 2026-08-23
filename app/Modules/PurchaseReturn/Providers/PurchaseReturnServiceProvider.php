<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Providers;

use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use App\Modules\PurchaseReturn\Repositories\PurchaseReturnRepository;
use Illuminate\Support\ServiceProvider;

class PurchaseReturnServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PurchaseReturnRepositoryInterface::class,
            PurchaseReturnRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
