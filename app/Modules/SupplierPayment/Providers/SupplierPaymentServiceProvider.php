<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Providers;

use App\Modules\SupplierPayment\Repositories\Contracts\SupplierPaymentRepositoryInterface;
use App\Modules\SupplierPayment\Repositories\SupplierPaymentRepository;
use Illuminate\Support\ServiceProvider;

class SupplierPaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SupplierPaymentRepositoryInterface::class,
            SupplierPaymentRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
