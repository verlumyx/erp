<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las columnas `sale_tax_id`, `purchase_tax_id` y `tax_id` se crearon sin foreign
 * key porque `app_taxes` todavía no existía. Ahora que el módulo de Impuestos
 * está en su sitio, se cierran las referencias.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('app_items', function (Blueprint $table) {
            $table->foreign('sale_tax_id')
                ->references('id')
                ->on('app_taxes')
                ->nullOnDelete();

            $table->foreign('purchase_tax_id')
                ->references('id')
                ->on('app_taxes')
                ->nullOnDelete();

            $table->index('sale_tax_id');
            $table->index('purchase_tax_id');
        });

        Schema::table('app_sales_order_lines', function (Blueprint $table) {
            $table->foreign('tax_id')
                ->references('id')
                ->on('app_taxes')
                ->nullOnDelete();
        });

        Schema::table('app_purchase_order_lines', function (Blueprint $table) {
            $table->foreign('tax_id')
                ->references('id')
                ->on('app_taxes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_items', function (Blueprint $table) {
            $table->dropForeign(['sale_tax_id']);
            $table->dropForeign(['purchase_tax_id']);
            $table->dropIndex(['sale_tax_id']);
            $table->dropIndex(['purchase_tax_id']);
        });

        Schema::table('app_sales_order_lines', function (Blueprint $table) {
            $table->dropForeign(['tax_id']);
        });

        Schema::table('app_purchase_order_lines', function (Blueprint $table) {
            $table->dropForeign(['tax_id']);
        });
    }
};
