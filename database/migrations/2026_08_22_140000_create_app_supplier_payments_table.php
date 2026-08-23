<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabecera del pago a proveedor. Es la salida de dinero que cancela una o
     * varias facturas; el reparto vive en
     * `app_supplier_payment_applications`.
     */
    public function up(): void
    {
        Schema::create('app_supplier_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->uuid('supplier_id');

            /**
             * Desde dónde se inició el pago. Se congela al crearlo y ya no
             * cambia: es lo que decide qué ofrece la pantalla. `advance` no es
             * un camino de creación —esos pagos nacen al aprobar un `ANP`—,
             * pero sí un origen posible.
             */
            $table->enum('origin_type', ['supplier', 'invoice', 'advance'])->default('supplier');

            /**
             * Id de la factura o del anticipo. Nulo con `origin_type =
             * supplier`. Sin foreign key: apunta a dos tablas distintas según
             * el tipo, y lo valida el Service antes de guardar.
             */
            $table->uuid('origin_id')->nullable();

            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'transfer', 'check', 'card', 'advance', 'credit_note', 'other'])
                ->default('transfer');
            $table->string('reference', 60)->nullable();
            $table->string('bank_account', 60)->nullable();

            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('base_currency', 3)->nullable();
            $table->decimal('base_exchange_rate', 18, 8)->nullable();

            $table->decimal('amount', 18, 2)->default(0);
            /** Retenciones practicadas al pagar: salen de la deuda sin salir en efectivo. */
            $table->decimal('withholding_amount', 18, 2)->default(0);
            $table->decimal('applied_amount', 18, 2)->default(0);
            $table->decimal('unapplied_amount', 18, 2)->default(0);

            /**
             * Importe en bolívares congelado: el pago tiene valor legal y lo
             * que salió en moneda local no se recalcula nunca. Contra el de la
             * factura sale el diferencial cambiario de cada aplicación.
             */
            $table->decimal('amount_ves', 18, 2)->default(0);

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['draft', 'confirmed', 'completed', 'cancelled'])
                ->default('draft');
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
            $table->index('payment_date');
            $table->index('payment_method');
            $table->index(['origin_type', 'origin_id']);

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('app_supplier_payments', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_supplier_payments');
    }
};
