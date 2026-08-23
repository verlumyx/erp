<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Providers;

use App\Modules\ItemStock\Repositories\Contracts\ItemStockRepositoryInterface;
use App\Modules\ItemStock\Repositories\ItemStockRepository;
use Illuminate\Support\ServiceProvider;

class ItemStockServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ItemStockRepositoryInterface::class,
            ItemStockRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
