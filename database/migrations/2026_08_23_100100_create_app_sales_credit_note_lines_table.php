<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de detalle: sin `code`, se edita desde la pantalla de la nota.
     */
    public function up(): void
    {
        Schema::create('app_sales_credit_note_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('sales_credit_note_id');

            $table->integer('line_number');
            $table->uuid('item_id');
            $table->uuid('measurement_unit_id');

            /** Línea de la factura original: da la trazabilidad de lo acreditado. */
            $table->uuid('sales_invoice_line_id')->nullable();

            /** Bodega a la que reingresa la mercancía si la nota afecta inventario. */
            $table->uuid('warehouse_id')->nullable();
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

            /**
             * Costo al que la mercancía reingresa: el mismo con el que salió en
             * la venta. Devolver no puede inventar ni destruir margen.
             */
            $table->decimal('unit_cost', 18, 6)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('sales_credit_note_id')
                ->references('id')
                ->on('app_sales_credit_notes')
                ->cascadeOnDelete();

            $table->foreign('item_id')
                ->references('id')
                ->on('app_items')
                ->restrictOnDelete();

            $table->foreign('measurement_unit_id')
                ->references('id')
                ->on('app_measurement_units')
                ->restrictOnDelete();

            $table->foreign('sales_invoice_line_id')
                ->references('id')
                ->on('app_sales_invoice_lines')
                ->restrictOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->nullOnDelete();

            $table->foreign('lot_id')
                ->references('id')
                ->on('app_item_lots')
                ->restrictOnDelete();

            $table->foreign('tax_id')
                ->references('id')
                ->on('app_taxes')
                ->nullOnDelete();

            $table->index('sales_credit_note_id');
            $table->index('item_id');
            $table->index('company_id');
            $table->index('status');
            $table->index('sales_invoice_line_id');

            $table->unique(['sales_credit_note_id', 'line_number'], 'app_sales_credit_note_lines_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_sales_credit_note_lines', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['sales_credit_note_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['measurement_unit_id']);
            $table->dropForeign(['sales_invoice_line_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['lot_id']);
            $table->dropForeign(['tax_id']);
        });

        Schema::dropIfExists('app_sales_credit_note_lines');
    }
};
