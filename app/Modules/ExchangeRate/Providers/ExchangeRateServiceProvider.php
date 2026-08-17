<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Providers;

use App\Modules\ExchangeRate\Repositories\Contracts\ExchangeRateRepositoryInterface;
use App\Modules\ExchangeRate\Repositories\ExchangeRateRepository;
use App\Modules\ExchangeRate\Services\Contracts\ExchangeRateResolverInterface;
use App\Modules\ExchangeRate\Services\ExchangeRateResolver;
use Illuminate\Support\ServiceProvider;

class ExchangeRateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ExchangeRateRepositoryInterface::class,
            ExchangeRateRepository::class,
        );

        /** Cachea por request: se comparte entre la cabecera y las líneas de un documento. */
        $this->app->scoped(
            ExchangeRateResolverInterface::class,
            ExchangeRateResolver::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
