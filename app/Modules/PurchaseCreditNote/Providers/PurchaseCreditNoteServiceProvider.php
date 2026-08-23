<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Providers;

use App\Modules\PurchaseCreditNote\Repositories\Contracts\PurchaseCreditNoteRepositoryInterface;
use App\Modules\PurchaseCreditNote\Repositories\PurchaseCreditNoteRepository;
use Illuminate\Support\ServiceProvider;

class PurchaseCreditNoteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PurchaseCreditNoteRepositoryInterface::class,
            PurchaseCreditNoteRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
