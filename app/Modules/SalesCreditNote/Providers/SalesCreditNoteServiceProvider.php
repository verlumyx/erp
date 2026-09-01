<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Providers;

use App\Modules\SalesCreditNote\Repositories\Contracts\SalesCreditNoteRepositoryInterface;
use App\Modules\SalesCreditNote\Repositories\SalesCreditNoteRepository;
use Illuminate\Support\ServiceProvider;

class SalesCreditNoteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SalesCreditNoteRepositoryInterface::class,
            SalesCreditNoteRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
