<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Providers;

use App\Modules\ClientAdvance\Repositories\ClientAdvanceRepository;
use App\Modules\ClientAdvance\Repositories\Contracts\ClientAdvanceRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class ClientAdvanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ClientAdvanceRepositoryInterface::class,
            ClientAdvanceRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
