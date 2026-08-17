<?php

declare(strict_types=1);

namespace App\Modules\Configuration\Providers;

use App\Modules\Configuration\Repositories\Contracts\ConfigurationRepositoryInterface;
use App\Modules\Configuration\Repositories\ConfigurationRepository;
use Illuminate\Support\ServiceProvider;

class ConfigurationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ConfigurationRepositoryInterface::class,
            ConfigurationRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
