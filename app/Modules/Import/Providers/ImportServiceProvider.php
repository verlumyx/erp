<?php

declare(strict_types=1);

namespace App\Modules\Import\Providers;

use App\Modules\Import\Repositories\Contracts\ImportRepositoryInterface;
use App\Modules\Import\Repositories\ImportRepository;
use Illuminate\Support\ServiceProvider;

class ImportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ImportRepositoryInterface::class,
            ImportRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
