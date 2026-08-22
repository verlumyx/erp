<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla de detalle: sin `code`, se edita desde la pantalla de la factura.
     */
    public function up(): void
    {
        Schema::create('app_sales_invoice_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('sales_invoice_id');

            $table->integer('line_number');

            /**
             * Línea origen. Mismo morph map que la cabecera; hoy solo admite
             * el alias `sales_order_line`.
             */
            $table->string('sourceable_type', 255)->nullable();
            $table->uuid('sourceable_id')->nullable();

            $table->uuid('item_id');
            $table->uuid('measurement_unit_id');

            /** Bodega de la línea si difiere de la de la cabecera. */
            $table->uuid('warehouse_id')->nullable();

            /**
             * Lote y serial de la mercancía despachada. Las foreign keys
             * contra app_item_lots y app_item_serials se agregan junto con el
             * módulo de Trazabilidad (Inventario), que aún no existe.
             */
            $table->uuid('lot_id')->nullable();
            $table->uuid('serial_id')->nullable();

            $table->decimal('quantity', 18, 4)->default(0);

            /** Cantidad convertida a la unidad base del artículo. */
            $table->decimal('base_quantity', 18, 4)->default(0);

            $table->decimal('unit_price', 18, 6)->default(0);

            $table->decimal('discount_percent', 7, 4)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);

            $table->uuid('tax_id')->nullable();
            $table->decimal('tax_percent', 7, 4)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('withholding_percent', 7, 4)->default(0);
            $table->decimal('withholding_amount', 18, 2)->default(0);

            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            /** Costo unitario al momento de la venta: se congela al confirmar. */
            $table->decimal('unit_cost', 18, 6)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->decimal('margin_amount', 18, 2)->default(0);

            /** Cantidad devuelta por el cliente. */
            $table->decimal('returned_quantity', 18, 4)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('sales_invoice_id')
                ->references('id')
                ->on('app_sales_invoices')
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

            $table->index('sales_invoice_id');
            $table->index('item_id');
            $table->index('company_id');
            $table->index('status');
            $table->index(['sourceable_type', 'sourceable_id']);

            $table->unique(['sales_invoice_id', 'line_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_sales_invoice_lines', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['sales_invoice_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['measurement_unit_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['tax_id']);
        });

        Schema::dropIfExists('app_sales_invoice_lines');
    }
};
