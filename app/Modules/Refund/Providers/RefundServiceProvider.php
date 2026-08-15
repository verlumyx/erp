<?php

declare(strict_types=1);

namespace App\Modules\Refund\Providers;

use App\Modules\Refund\Repositories\Contracts\RefundRepositoryInterface;
use App\Modules\Refund\Repositories\RefundRepository;
use Illuminate\Support\ServiceProvider;

class RefundServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            RefundRepositoryInterface::class,
            RefundRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
