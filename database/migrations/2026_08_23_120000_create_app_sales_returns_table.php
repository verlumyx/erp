<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabecera de la devolución de venta: reingreso físico de mercancía que el
     * cliente devuelve por defectos, exceso o cancelación de su pedido.
     */
    public function up(): void
    {
        Schema::create('app_sales_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->uuid('client_id');

            /** Factura de origen. Una devolución puede nacer sin ella. */
            $table->uuid('sales_invoice_id')->nullable();

            /**
             * Despacho de origen. Sin foreign key hasta que exista el módulo
             * Despachos de Logística (`app_dispatches`).
             */
            $table->uuid('dispatch_id')->nullable();

            /** Bodega a la que reingresa la mercancía. La línea elige la ubicación. */
            $table->uuid('warehouse_id');

            $table->date('return_date');

            $table->enum('reason', [
                'damaged', 'wrong_item', 'expired', 'excess', 'quality', 'client_cancellation', 'other',
            ])->default('damaged');
            $table->string('reason_detail', 500)->nullable();

            /**
             * En qué estado vuelve la mercancía, y con ello a dónde va:
             * `resalable` reingresa a la bodega de venta, `damaged` a una de
             * cuarentena y `scrap` no reingresa —se destruye y la pérdida se
             * registra por Ajuste—.
             */
            $table->enum('condition', ['resalable', 'damaged', 'scrap'])->default('resalable');

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

            /** Quién recibió físicamente la mercancía en la bodega. */
            $table->uuid('received_by')->nullable();

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

            $table->foreign('client_id')
                ->references('id')
                ->on('app_clients')
                ->restrictOnDelete();

            $table->foreign('sales_invoice_id')
                ->references('id')
                ->on('app_sales_invoices')
                ->restrictOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->restrictOnDelete();

            $table->foreign('credit_note_id')
                ->references('id')
                ->on('app_sales_credit_notes')
                ->nullOnDelete();

            /** Foreign key: quién recibió la mercancía (trazabilidad) */
            $table->foreign('received_by')
                ->references('id')
                ->on('users')
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
            $table->index('client_id');
            $table->index('sales_invoice_id');
            $table->index('return_date');
            $table->index('warehouse_id');

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('app_sales_returns', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['sales_invoice_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['credit_note_id']);
            $table->dropForeign(['received_by']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_sales_returns');
    }
};
