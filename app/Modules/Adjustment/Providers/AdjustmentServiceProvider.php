<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Providers;

use App\Modules\Adjustment\Repositories\AdjustmentRepository;
use App\Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AdjustmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AdjustmentRepositoryInterface::class,
            AdjustmentRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
