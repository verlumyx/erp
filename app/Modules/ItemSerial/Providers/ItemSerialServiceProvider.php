<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Providers;

use App\Modules\ItemSerial\Repositories\Contracts\ItemSerialRepositoryInterface;
use App\Modules\ItemSerial\Repositories\ItemSerialRepository;
use Illuminate\Support\ServiceProvider;

class ItemSerialServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ItemSerialRepositoryInterface::class,
            ItemSerialRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
