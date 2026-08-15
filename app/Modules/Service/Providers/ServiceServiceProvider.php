<?php

declare(strict_types=1);

namespace App\Modules\Service\Providers;

use App\Modules\Service\Repositories\Contracts\ServiceRepositoryInterface;
use App\Modules\Service\Repositories\ServiceRepository;
use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ServiceRepositoryInterface::class,
            ServiceRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
