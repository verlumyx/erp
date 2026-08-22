<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Providers;

use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use App\Modules\PurchaseInvoice\Repositories\PurchaseInvoiceRepository;
use Illuminate\Support\ServiceProvider;

class PurchaseInvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PurchaseInvoiceRepositoryInterface::class,
            PurchaseInvoiceRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
