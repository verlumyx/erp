<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Providers;

use App\Modules\PriceList\Repositories\Contracts\PriceListRepositoryInterface;
use App\Modules\PriceList\Repositories\PriceListRepository;
use Illuminate\Support\ServiceProvider;

class PriceListServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PriceListRepositoryInterface::class,
            PriceListRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
