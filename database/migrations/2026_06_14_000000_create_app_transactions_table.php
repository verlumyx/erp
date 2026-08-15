<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('type', 10);
            $table->string('category', 50);
            $table->string('subcategory', 100)->nullable();
            $table->string('related_type', 50)->nullable();
            $table->uuid('related_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('date');
            $table->string('payment_method', 100);
            $table->string('reference', 100)->nullable();
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->string('description', 255);
            $table->text('notes')->nullable();
            $table->uuid('recorded_by')->nullable();
            $table->string('receipt_url', 500)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->cascadeOnDelete();

            // El histórico contable se preserva aunque se elimine el usuario.
            $table->foreign('recorded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('company_id');
            $table->index('date', 'idx_transactions_date');
            $table->index(['type', 'date'], 'idx_transactions_type_date');
            $table->index(['type', 'category', 'date'], 'idx_transactions_type_category_date');
            $table->index(['related_type', 'related_id'], 'idx_transactions_related');
        });

        // CHECKs: SQLite no soporta ADD CONSTRAINT vía ALTER,
        // así que solo se aplican en motores que lo permiten (PostgreSQL).
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE app_transactions ADD CONSTRAINT app_transactions_type_check CHECK (type IN ('income','expense'))");
            DB::statement("ALTER TABLE app_transactions ADD CONSTRAINT app_transactions_category_check CHECK (category IN ('streaming_account','streaming_account_renewal','petty_cash','salary','commission','utilities','tools','marketing','other_expense','sale','renewal','partner_contribution','refund','other_income'))");
            DB::statement('ALTER TABLE app_transactions ADD CONSTRAINT app_transactions_amount_check CHECK (amount >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_transactions');
    }
};
