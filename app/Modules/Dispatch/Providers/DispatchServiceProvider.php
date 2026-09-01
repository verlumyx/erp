<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Providers;

use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use App\Modules\Dispatch\Repositories\DispatchRepository;
use Illuminate\Support\ServiceProvider;

class DispatchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            DispatchRepositoryInterface::class,
            DispatchRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
