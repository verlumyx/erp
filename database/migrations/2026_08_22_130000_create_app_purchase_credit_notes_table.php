<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabecera de la nota de crédito a proveedor. Disminuye la deuda con el
     * proveedor: descuentos posteriores, devoluciones o correcciones de precio.
     */
    public function up(): void
    {
        Schema::create('app_purchase_credit_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->uuid('supplier_id');

            /** Factura afectada. Una nota puede nacer sin ella (descuento global). */
            $table->uuid('purchase_invoice_id')->nullable();

            /**
             * Devolución que origina la nota. Sin foreign key hasta que exista
             * el módulo Devoluciones de compras (`app_purchase_returns`).
             */
            $table->uuid('purchase_return_id')->nullable();

            /** Número de la nota tal como la emitió el proveedor. */
            $table->string('supplier_document_number', 60)->nullable();

            $table->date('note_date');

            $table->enum('reason', ['return', 'discount', 'price_correction', 'damaged', 'other'])
                ->default('return');
            $table->string('reason_detail', 500)->nullable();

            /** `yes` cuando la nota implica salida física de mercancía. */
            $table->enum('affects_inventory', ['yes', 'no'])->default('no');

            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('base_currency', 3)->nullable();
            $table->decimal('base_exchange_rate', 18, 8)->nullable();

            /** Totales derivados de las líneas activas. */
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            /**
             * Importes en bolívares congelados: la nota de crédito es un
             * documento fiscal y su valor en moneda local no se recalcula.
             * Solo en la cabecera; las líneas viven en la moneda del documento.
             */
            $table->decimal('subtotal_ves', 18, 2)->default(0);
            $table->decimal('tax_amount_ves', 18, 2)->default(0);
            $table->decimal('total_ves', 18, 2)->default(0);

            /** Los mueven las aplicaciones a facturas (`credit_note`). */
            $table->decimal('applied_amount', 18, 2)->default(0);
            $table->decimal('balance', 18, 2)->default(0);

            $table->timestamp('cancelled_at')->nullable();
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

            $table->foreign('purchase_invoice_id')
                ->references('id')
                ->on('app_purchase_invoices')
                ->restrictOnDelete();

            /** Foreign key: quién registró la nota (trazabilidad) */
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
            $table->index('purchase_invoice_id');
            $table->index('note_date');
            $table->index('balance');

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('app_purchase_credit_notes', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['purchase_invoice_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_purchase_credit_notes');
    }
};
