<?php

declare(strict_types=1);

namespace App\Modules\Store\Providers;

use App\Modules\Store\Repositories\Contracts\StoreCustomerRepositoryInterface;
use App\Modules\Store\Repositories\Contracts\StoreItemRepositoryInterface;
use App\Modules\Store\Repositories\Contracts\StoreOrderRepositoryInterface;
use App\Modules\Store\Repositories\Contracts\StoreSettingRepositoryInterface;
use App\Modules\Store\Repositories\StoreCustomerRepository;
use App\Modules\Store\Repositories\StoreItemRepository;
use App\Modules\Store\Repositories\StoreOrderRepository;
use App\Modules\Store\Repositories\StoreSettingRepository;
use Illuminate\Support\ServiceProvider;

class StoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StoreSettingRepositoryInterface::class, StoreSettingRepository::class);
        $this->app->bind(StoreItemRepositoryInterface::class, StoreItemRepository::class);
        $this->app->bind(StoreCustomerRepositoryInterface::class, StoreCustomerRepository::class);
        $this->app->bind(StoreOrderRepositoryInterface::class, StoreOrderRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
