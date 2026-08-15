<?php

declare(strict_types=1);

namespace App\Modules\Permission\Providers;

use App\Modules\Permission\Repositories\Contracts\PermissionRepositoryInterface;
use App\Modules\Permission\Repositories\PermissionRepository;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PermissionRepositoryInterface::class,
            PermissionRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
