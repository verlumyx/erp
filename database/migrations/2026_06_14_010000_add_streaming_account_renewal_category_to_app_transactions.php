<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agrega la categoría 'streaming_account_renewal' al CHECK de categorías
     * para distinguir la compra inicial de una cuenta de sus renovaciones.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE app_transactions DROP CONSTRAINT IF EXISTS app_transactions_category_check');
        DB::statement("ALTER TABLE app_transactions ADD CONSTRAINT app_transactions_category_check CHECK (category IN ('streaming_account','streaming_account_renewal','petty_cash','salary','commission','utilities','tools','marketing','other_expense','sale','renewal','partner_contribution','refund','other_income'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE app_transactions DROP CONSTRAINT IF EXISTS app_transactions_category_check');
        DB::statement("ALTER TABLE app_transactions ADD CONSTRAINT app_transactions_category_check CHECK (category IN ('streaming_account','petty_cash','salary','commission','utilities','tools','marketing','other_expense','sale','renewal','partner_contribution','refund','other_income'))");
    }
};
