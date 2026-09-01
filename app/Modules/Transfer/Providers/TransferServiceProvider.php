<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Providers;

use App\Modules\Transfer\Repositories\Contracts\TransferRepositoryInterface;
use App\Modules\Transfer\Repositories\TransferRepository;
use Illuminate\Support\ServiceProvider;

class TransferServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TransferRepositoryInterface::class,
            TransferRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
