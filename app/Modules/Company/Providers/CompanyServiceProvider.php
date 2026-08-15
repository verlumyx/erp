<?php

declare(strict_types=1);

namespace App\Modules\Company\Providers;

use App\Modules\Company\Repositories\CompanyRepository;
use App\Modules\Company\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class CompanyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CompanyRepositoryInterface::class,
            CompanyRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
