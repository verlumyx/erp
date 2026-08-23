<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Qué documento paga qué factura. La comparten los pagos, los anticipos y
     * las notas de crédito, por eso el origen viaja como par
     * (`source_type`, `source_id`) y no como foreign key.
     *
     * Es tabla de detalle: lleva `company_id` y `status`, pero no `code`.
     */
    public function up(): void
    {
        Schema::create('app_supplier_payment_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();

            $table->uuid('purchase_invoice_id');

            /**
             * Origen del crédito con el que se abona la factura. Sin foreign
             * key: apunta a tres tablas distintas según el tipo.
             */
            $table->enum('source_type', ['payment', 'advance', 'credit_note']);
            $table->uuid('source_id');

            $table->decimal('applied_amount', 18, 2)->default(0);
            $table->dateTime('applied_at');

            $table->decimal('exchange_rate', 18, 8)->default(1);
            /**
             * Diferencia en bolívares entre lo que la factura congeló al
             * emitirse y lo que este documento congeló al pagarse. En moneda
             * del documento la deuda queda saldada exacta; en bolívares sobra
             * o falta, y eso es una cuenta contable real.
             */
            $table->decimal('exchange_difference', 18, 2)->default(0);

            $table->enum('status', ['active', 'reversed'])->default('active');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('purchase_invoice_id')
                ->references('id')
                ->on('app_purchase_invoices')
                ->restrictOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('company_id');
            $table->index('status');
            $table->index('purchase_invoice_id');
            $table->index(['source_type', 'source_id']);
            $table->index('applied_at');

            /**
             * Un documento abona una factura una sola vez: si hay que
             * corregir el monto se reescribe esa fila, no se agrega otra. Una
             * fila revertida conserva su lugar (política de no borrado).
             */
            $table->unique(
                ['purchase_invoice_id', 'source_type', 'source_id'],
                'app_supplier_payment_applications_source_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('app_supplier_payment_applications', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['purchase_invoice_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_supplier_payment_applications');
    }
};
