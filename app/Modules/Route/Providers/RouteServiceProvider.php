<?php

declare(strict_types=1);

namespace App\Modules\Route\Providers;

use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;
use App\Modules\Route\Repositories\RouteRepository;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            RouteRepositoryInterface::class,
            RouteRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
