<?php

declare(strict_types=1);

namespace App\Modules\Pos\Providers;

use App\Modules\Pos\Repositories\Contracts\PosRegisterRepositoryInterface;
use App\Modules\Pos\Repositories\Contracts\PosSaleRepositoryInterface;
use App\Modules\Pos\Repositories\Contracts\PosSessionRepositoryInterface;
use App\Modules\Pos\Repositories\PosRegisterRepository;
use App\Modules\Pos\Repositories\PosSaleRepository;
use App\Modules\Pos\Repositories\PosSessionRepository;
use Illuminate\Support\ServiceProvider;

class PosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PosRegisterRepositoryInterface::class,
            PosRegisterRepository::class,
        );

        $this->app->bind(
            PosSessionRepositoryInterface::class,
            PosSessionRepository::class,
        );

        $this->app->bind(
            PosSaleRepositoryInterface::class,
            PosSaleRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
