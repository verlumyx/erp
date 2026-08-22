<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de detalle: sin `code`, se edita desde la pantalla de la factura.
     */
    public function up(): void
    {
        Schema::create('app_purchase_invoice_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('purchase_invoice_id');

            $table->integer('line_number');
            $table->uuid('item_id');
            $table->uuid('measurement_unit_id');

            /** Línea origen polimórfica: hoy solo `purchase_order_line`. */
            $table->string('sourceable_type', 255)->nullable();
            $table->uuid('sourceable_id')->nullable();

            /** Bodega de la línea si difiere de la cabecera. */
            $table->uuid('warehouse_id')->nullable();

            /**
             * `lot_id` queda sin foreign key hasta que exista el módulo de
             * lotes (`app_item_lots`).
             */
            $table->uuid('lot_id')->nullable();

            $table->decimal('quantity', 18, 4);
            $table->decimal('base_quantity', 18, 4);
            $table->decimal('unit_price', 18, 6);

            $table->decimal('discount_percent', 7, 4)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);

            $table->uuid('tax_id')->nullable();
            $table->decimal('tax_percent', 7, 4)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('withholding_percent', 7, 4)->default(0);
            $table->decimal('withholding_amount', 18, 2)->default(0);

            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            /** Costo unitario final con el flete y los gastos prorrateados. */
            $table->decimal('landed_cost', 18, 6)->default(0);

            /** Lo mueven las Devoluciones de compras. */
            $table->decimal('returned_quantity', 18, 4)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('purchase_invoice_id')
                ->references('id')
                ->on('app_purchase_invoices')
                ->cascadeOnDelete();

            $table->foreign('item_id')
                ->references('id')
                ->on('app_items')
                ->restrictOnDelete();

            $table->foreign('measurement_unit_id')
                ->references('id')
                ->on('app_measurement_units')
                ->restrictOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->nullOnDelete();

            $table->foreign('tax_id')
                ->references('id')
                ->on('app_taxes')
                ->nullOnDelete();

            $table->index('purchase_invoice_id');
            $table->index('item_id');
            $table->index('company_id');
            $table->index('status');
            $table->index(['sourceable_type', 'sourceable_id']);

            $table->unique(['purchase_invoice_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::table('app_purchase_invoice_lines', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['purchase_invoice_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['measurement_unit_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['tax_id']);
        });

        Schema::dropIfExists('app_purchase_invoice_lines');
    }
};
