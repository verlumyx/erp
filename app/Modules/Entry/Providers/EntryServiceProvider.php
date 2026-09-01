<?php

declare(strict_types=1);

namespace App\Modules\Entry\Providers;

use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use App\Modules\Entry\Repositories\EntryRepository;
use Illuminate\Support\ServiceProvider;

class EntryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            EntryRepositoryInterface::class,
            EntryRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
