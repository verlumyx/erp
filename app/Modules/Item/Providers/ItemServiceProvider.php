<?php

declare(strict_types=1);

namespace App\Modules\Item\Providers;

use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\Item\Repositories\ItemRepository;
use Illuminate\Support\ServiceProvider;

class ItemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ItemRepositoryInterface::class,
            ItemRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
