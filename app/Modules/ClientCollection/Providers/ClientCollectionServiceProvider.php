<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Providers;

use App\Modules\ClientCollection\Repositories\ClientCollectionRepository;
use App\Modules\ClientCollection\Repositories\Contracts\ClientCollectionRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class ClientCollectionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ClientCollectionRepositoryInterface::class,
            ClientCollectionRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
