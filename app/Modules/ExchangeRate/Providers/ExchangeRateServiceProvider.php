<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Providers;

use App\Modules\ExchangeRate\Repositories\Contracts\ExchangeRateRepositoryInterface;
use App\Modules\ExchangeRate\Repositories\ExchangeRateRepository;
use Illuminate\Support\ServiceProvider;

class ExchangeRateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ExchangeRateRepositoryInterface::class,
            ExchangeRateRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
