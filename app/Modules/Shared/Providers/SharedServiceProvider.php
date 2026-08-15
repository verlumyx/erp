<?php

declare(strict_types=1);

namespace App\Modules\Shared\Providers;

use App\Modules\Shared\Repositories\Contracts\UserCompanyRepositoryInterface;
use App\Modules\Shared\Repositories\UserCompanyRepository;
use Illuminate\Support\ServiceProvider;

class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserCompanyRepositoryInterface::class,
            UserCompanyRepository::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
