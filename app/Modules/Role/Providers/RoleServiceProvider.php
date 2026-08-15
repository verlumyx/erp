<?php

declare(strict_types=1);

namespace App\Modules\Role\Providers;

use App\Modules\Role\Repositories\Contracts\RoleRepositoryInterface;
use App\Modules\Role\Repositories\RoleRepository;
use Illuminate\Support\ServiceProvider;

class RoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            RoleRepositoryInterface::class,
            RoleRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
