<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabecera de la devolución de compra: salida física de mercancía hacia el
     * proveedor por defectos, exceso o error de despacho.
     */
    public function up(): void
    {
        Schema::create('app_purchase_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->uuid('supplier_id');

            /** Factura de origen. Una devolución puede nacer sin ella. */
            $table->uuid('purchase_invoice_id')->nullable();

            /**
             * Entrada de origen. Sin foreign key hasta que exista el módulo
             * Entradas de Logística (`app_entries`).
             */
            $table->uuid('entry_id')->nullable();

            /** Bodega desde la que sale la mercancía. La línea elige la ubicación. */
            $table->uuid('warehouse_id');

            $table->date('return_date');

            $table->enum('reason', ['damaged', 'wrong_item', 'expired', 'excess', 'quality', 'other'])
                ->default('damaged');
            $table->string('reason_detail', 500)->nullable();

            /**
             * La devolución no es un documento fiscal —lo es la nota de crédito
             * que genera—, así que congela las tasas para reexpresar sus
             * importes pero no persiste montos en bolívares.
             */
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('base_currency', 3)->nullable();
            $table->decimal('base_exchange_rate', 18, 8)->nullable();

            /** Totales derivados de las líneas activas. */
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            /** Nota de crédito que acredita la devolución, cuando ya se emitió. */
            $table->uuid('credit_note_id')->nullable();

            $table->string('carrier', 150)->nullable();
            $table->string('tracking_number', 60)->nullable();

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

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->restrictOnDelete();

            $table->foreign('credit_note_id')
                ->references('id')
                ->on('app_purchase_credit_notes')
                ->nullOnDelete();

            /** Foreign key: quién registró la devolución (trazabilidad) */
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
            $table->index('return_date');
            $table->index('warehouse_id');

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('app_purchase_returns', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['purchase_invoice_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['credit_note_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_purchase_returns');
    }
};
