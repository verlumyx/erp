<?php

declare(strict_types=1);

namespace App\Modules\Menu\Providers;

use App\Modules\Menu\Repositories\Contracts\MenuRepositoryInterface;
use App\Modules\Menu\Repositories\MenuRepository;
use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            MenuRepositoryInterface::class,
            MenuRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
