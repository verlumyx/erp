<?php

declare(strict_types=1);

namespace App\Modules\Currency\Providers;

use App\Modules\Currency\Repositories\Contracts\CurrencyRepositoryInterface;
use App\Modules\Currency\Repositories\CurrencyRepository;
use Illuminate\Support\ServiceProvider;

/**
 * El catálogo de monedas es de solo lectura: no tiene rutas ni pantallas
 * propias, solo el binding del repositorio que consumen el resto de módulos.
 */
class CurrencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            CurrencyRepositoryInterface::class,
            CurrencyRepository::class,
        );
    }
}
