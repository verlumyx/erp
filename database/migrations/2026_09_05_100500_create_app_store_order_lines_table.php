<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Líneas del pedido web. Estructura común de líneas con dos
     * particularidades: la unidad es siempre la base del artículo y el
     * impuesto va en cero porque se resuelve al convertir.
     */
    public function up(): void
    {
        Schema::create('app_store_order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('store_order_id');

            $table->integer('line_number');
            $table->uuid('item_id');

            /** Desde qué publicación se pidió. */
            $table->uuid('store_item_id');

            /** Siempre la unidad base del artículo: la tienda no ofrece alternas. */
            $table->uuid('measurement_unit_id');

            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('base_quantity', 18, 4)->default(0);

            /** `unit_price` y `list_price` son iguales: el precio de lista al momento del pedido. */
            $table->decimal('unit_price', 18, 6)->default(0);
            $table->decimal('list_price', 18, 6)->default(0);

            $table->decimal('discount_percent', 7, 4)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);

            $table->uuid('tax_id')->nullable();
            $table->decimal('tax_percent', 7, 4)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('withholding_percent', 7, 4)->default(0);
            $table->decimal('withholding_amount', 18, 2)->default(0);

            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('store_order_id')
                ->references('id')
                ->on('app_store_orders')
                ->cascadeOnDelete();

            $table->foreign('item_id')
                ->references('id')
                ->on('app_items')
                ->restrictOnDelete();

            $table->foreign('store_item_id')
                ->references('id')
                ->on('app_store_items')
                ->restrictOnDelete();

            $table->foreign('measurement_unit_id')
                ->references('id')
                ->on('app_measurement_units')
                ->restrictOnDelete();

            $table->foreign('tax_id')
                ->references('id')
                ->on('app_taxes')
                ->restrictOnDelete();

            $table->index('company_id');
            $table->index('status');
            $table->index('store_order_id');
            $table->index('item_id');
            $table->index('store_item_id');
            $table->unique(['store_order_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::table('app_store_order_lines', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['store_order_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['store_item_id']);
            $table->dropForeign(['measurement_unit_id']);
            $table->dropForeign(['tax_id']);
        });

        Schema::dropIfExists('app_store_order_lines');
    }
};
