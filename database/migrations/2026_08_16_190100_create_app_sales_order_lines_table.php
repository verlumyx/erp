<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla de detalle: sin `code`, se edita desde la pantalla del pedido.
     */
    public function up(): void
    {
        Schema::create('app_sales_order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('sales_order_id');

            $table->integer('line_number');
            $table->uuid('item_id');
            $table->uuid('measurement_unit_id');

            $table->decimal('quantity', 18, 4)->default(0);

            /** Cantidad convertida a la unidad base del artículo. */
            $table->decimal('base_quantity', 18, 4)->default(0);

            $table->decimal('unit_price', 18, 6)->default(0);

            /** Precio de lista antes del descuento; para medir el descuento real. */
            $table->decimal('list_price', 18, 6)->default(0);

            $table->decimal('discount_percent', 7, 4)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);

            /**
             * Impuesto de la línea. La foreign key contra app_taxes se agrega
             * junto con el módulo de Impuestos, que aún no existe.
             */
            $table->uuid('tax_id')->nullable();
            $table->decimal('tax_percent', 7, 4)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('withholding_percent', 7, 4)->default(0);
            $table->decimal('withholding_amount', 18, 2)->default(0);

            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            /** Avance de la línea: la reserva se libera al despachar o al anular. */
            $table->decimal('reserved_quantity', 18, 4)->default(0);
            $table->decimal('dispatched_quantity', 18, 4)->default(0);
            $table->decimal('invoiced_quantity', 18, 4)->default(0);
            $table->decimal('pending_quantity', 18, 4)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('sales_order_id')
                ->references('id')
                ->on('app_sales_orders')
                ->cascadeOnDelete();

            $table->foreign('item_id')
                ->references('id')
                ->on('app_items')
                ->restrictOnDelete();

            $table->foreign('measurement_unit_id')
                ->references('id')
                ->on('app_measurement_units')
                ->restrictOnDelete();

            $table->index('sales_order_id');
            $table->index('item_id');
            $table->index('company_id');
            $table->index('status');

            $table->unique(['sales_order_id', 'line_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_sales_order_lines', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['sales_order_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['measurement_unit_id']);
        });

        Schema::dropIfExists('app_sales_order_lines');
    }
};
