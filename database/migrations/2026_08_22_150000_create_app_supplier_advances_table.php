<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anticipo a proveedor: dinero entregado antes de recibir la factura, que
     * queda como saldo a favor aplicable a facturas futuras. No tiene líneas.
     */
    public function up(): void
    {
        Schema::create('app_supplier_advances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->uuid('supplier_id');

            /** Orden que motiva el anticipo. Un anticipo puede nacer sin ella. */
            $table->uuid('purchase_order_id')->nullable();

            $table->date('advance_date');

            $table->enum('payment_method', ['cash', 'transfer', 'check', 'card', 'other'])
                ->default('transfer');
            $table->string('reference', 60)->nullable();
            $table->string('bank_account', 60)->nullable();

            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('base_currency', 3)->nullable();
            $table->decimal('base_exchange_rate', 18, 8)->nullable();

            /** Monto entregado. El pago espejo lo copia tal cual. */
            $table->decimal('amount', 18, 2)->default(0);
            /** Lo mueven las aplicaciones a facturas (`advance`). */
            $table->decimal('applied_amount', 18, 2)->default(0);
            $table->decimal('balance', 18, 2)->default(0);

            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();

            /**
             * `pending_confirmation` es el anticipo aprobado cuyo pago espejo
             * todavía no se confirma: comprometido, no entregado.
             */
            $table->enum('status', [
                'draft',
                'pending_confirmation',
                'confirmed',
                'partial',
                'completed',
                'cancelled',
            ])->default('draft');

            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('supplier_id')
                ->references('id')
                ->on('app_suppliers')
                ->restrictOnDelete();

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('app_purchase_orders')
                ->restrictOnDelete();

            /** Foreign key: quién registró el anticipo (trazabilidad) */
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices base
            $table->index('company_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');

            // Índices propios del módulo
            $table->index('supplier_id');
            $table->index('purchase_order_id');
            $table->index('advance_date');
            $table->index('balance');

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('app_supplier_advances', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_supplier_advances');
    }
};
