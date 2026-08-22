<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Providers;

use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use App\Modules\SalesInvoice\Repositories\SalesInvoiceRepository;
use Illuminate\Support\ServiceProvider;

class SalesInvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SalesInvoiceRepositoryInterface::class,
            SalesInvoiceRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
