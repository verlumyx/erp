<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Moneda principal de la empresa congelada en el documento, junto con su
     * tasa del día. El par `currency`/`exchange_rate` dice cuántos bolívares
     * vale la orden; este segundo par permite reexpresarla en la moneda de la
     * empresa aunque esa moneda cambie mañana.
     *
     * Nulas a propósito: las órdenes anteriores a este cambio no tienen tasa
     * resuelta y, como solo se editan en borrador, la estrenan al guardarse.
     */
    private const TABLES = ['app_sales_orders', 'app_purchase_orders'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->string('base_currency', 3)->nullable()->after('exchange_rate');
                $table->decimal('base_exchange_rate', 18, 8)->nullable()->after('base_currency');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropColumn(['base_currency', 'base_exchange_rate']);
            });
        }
    }
};
