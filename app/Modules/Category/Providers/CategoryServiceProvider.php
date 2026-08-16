<?php

declare(strict_types=1);

namespace App\Modules\Category\Providers;

use App\Modules\Category\Repositories\CategoryRepository;
use App\Modules\Category\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class CategoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CategoryRepositoryInterface::class,
            CategoryRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
