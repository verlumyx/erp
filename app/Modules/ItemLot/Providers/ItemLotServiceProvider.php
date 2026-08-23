<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Providers;

use App\Modules\ItemLot\Repositories\Contracts\ItemLotRepositoryInterface;
use App\Modules\ItemLot\Repositories\ItemLotRepository;
use Illuminate\Support\ServiceProvider;

class ItemLotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ItemLotRepositoryInterface::class,
            ItemLotRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
