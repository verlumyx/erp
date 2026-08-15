<?php

declare(strict_types=1);

namespace App\Modules\Lead\Providers;

use App\Modules\Lead\Repositories\Contracts\LeadRepositoryInterface;
use App\Modules\Lead\Repositories\LeadRepository;
use Illuminate\Support\ServiceProvider;

class LeadServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            LeadRepositoryInterface::class,
            LeadRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
