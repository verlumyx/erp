<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Providers;

use App\Modules\ClientType\Repositories\ClientTypeRepository;
use App\Modules\ClientType\Repositories\Contracts\ClientTypeRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class ClientTypeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ClientTypeRepositoryInterface::class,
            ClientTypeRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
