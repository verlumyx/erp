<?php

declare(strict_types=1);

namespace App\Modules\Client\Providers;

use App\Modules\Client\Repositories\ClientRepository;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class ClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ClientRepositoryInterface::class,
            ClientRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
