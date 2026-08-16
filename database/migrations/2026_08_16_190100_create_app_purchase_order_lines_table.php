<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla de detalle: sin `code`, se edita desde la pantalla de la orden.
     */
    public function up(): void
    {
        Schema::create('app_purchase_order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('purchase_order_id');

            $table->integer('line_number');
            $table->uuid('item_id');
            $table->uuid('measurement_unit_id');

            $table->decimal('quantity', 18, 4);
            $table->decimal('base_quantity', 18, 4);
            $table->decimal('unit_price', 18, 6);

            $table->decimal('discount_percent', 7, 4)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);

            /**
             * `tax_id` queda sin foreign key hasta que exista el módulo Impuestos
             * (`app_taxes`). Mientras tanto el porcentaje se captura en la línea.
             */
            $table->uuid('tax_id')->nullable();
            $table->decimal('tax_percent', 7, 4)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('withholding_percent', 7, 4)->default(0);
            $table->decimal('withholding_amount', 18, 2)->default(0);

            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            /** Avance de la línea: lo mueven las Entradas y las Facturas de compra. */
            $table->decimal('received_quantity', 18, 4)->default(0);
            $table->decimal('invoiced_quantity', 18, 4)->default(0);
            $table->decimal('pending_quantity', 18, 4)->default(0);

            $table->date('expected_date')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('app_purchase_orders')
                ->cascadeOnDelete();

            $table->foreign('item_id')
                ->references('id')
                ->on('app_items')
                ->restrictOnDelete();

            $table->foreign('measurement_unit_id')
                ->references('id')
                ->on('app_measurement_units')
                ->restrictOnDelete();

            $table->index('purchase_order_id');
            $table->index('item_id');
            $table->index('company_id');
            $table->index('status');

            $table->unique(['purchase_order_id', 'line_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_purchase_order_lines', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['measurement_unit_id']);
        });

        Schema::dropIfExists('app_purchase_order_lines');
    }
};
