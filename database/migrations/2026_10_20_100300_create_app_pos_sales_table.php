<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ventas POS / Tickets de mostrador: cabeceras de ventas rápidas.
     */
    public function up(): void
    {
        Schema::create('app_pos_sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();
            $table->uuid('pos_session_id');
            $table->uuid('pos_register_id');
            $table->uuid('client_id');
            $table->uuid('sales_invoice_id')->nullable();
            $table->uuid('dispatch_id')->nullable();
            $table->timestamp('sale_date');

            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);

            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('igtf_amount', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->decimal('total_in_local_currency', 18, 2)->default(0);

            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->decimal('change_amount_usd', 18, 2)->default(0);
            $table->decimal('change_amount_ves', 18, 2)->default(0);

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->uuid('cancelled_by')->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['draft', 'completed', 'cancelled'])->default('completed');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('pos_session_id')
                ->references('id')
                ->on('app_pos_sessions')
                ->restrictOnDelete();

            $table->foreign('pos_register_id')
                ->references('id')
                ->on('app_pos_registers')
                ->restrictOnDelete();

            $table->foreign('client_id')
                ->references('id')
                ->on('app_clients')
                ->restrictOnDelete();

            $table->foreign('sales_invoice_id')
                ->references('id')
                ->on('app_sales_invoices')
                ->nullOnDelete();

            $table->foreign('dispatch_id')
                ->references('id')
                ->on('app_dispatches')
                ->nullOnDelete();

            $table->foreign('cancelled_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices base
            $table->index('company_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');
            $table->unique(['company_id', 'code']);

            // Índices propios
            $table->index('pos_session_id');
            $table->index('pos_register_id');
            $table->index('client_id');
            $table->index('sale_date');
            $table->index('sales_invoice_id');
            $table->index('dispatch_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_pos_sales', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['pos_session_id']);
            $table->dropForeign(['pos_register_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['sales_invoice_id']);
            $table->dropForeign(['dispatch_id']);
            $table->dropForeign(['cancelled_by']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_pos_sales');
    }
};
