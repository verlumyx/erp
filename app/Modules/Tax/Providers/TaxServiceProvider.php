<?php

declare(strict_types=1);

namespace App\Modules\Tax\Providers;

use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;
use App\Modules\Tax\Repositories\TaxRepository;
use Illuminate\Support\ServiceProvider;

class TaxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TaxRepositoryInterface::class,
            TaxRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
