<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Líneas de venta POS: desglose de productos vendidos en un ticket.
     */
    public function up(): void
    {
        Schema::create('app_pos_sale_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('pos_sale_id');
            $table->integer('line_number');
            $table->uuid('item_id');
            $table->uuid('measurement_unit_id');

            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('base_quantity', 18, 4)->default(1);
            $table->decimal('unit_price', 18, 6)->default(0);
            $table->decimal('unit_cost', 18, 6)->default(0);

            $table->decimal('discount_percent', 7, 4)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);

            $table->uuid('tax_id')->nullable();
            $table->decimal('tax_percent', 7, 4)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);

            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('pos_sale_id')
                ->references('id')
                ->on('app_pos_sales')
                ->cascadeOnDelete();

            $table->foreign('item_id')
                ->references('id')
                ->on('app_items')
                ->restrictOnDelete();

            $table->foreign('measurement_unit_id')
                ->references('id')
                ->on('app_measurement_units')
                ->restrictOnDelete();

            $table->foreign('tax_id')
                ->references('id')
                ->on('app_taxes')
                ->nullOnDelete();

            // Índices base
            $table->index('company_id');
            $table->index('status');
            $table->index('created_at');

            // Índices propios
            $table->index('pos_sale_id');
            $table->index('item_id');
            $table->unique(['pos_sale_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::table('app_pos_sale_lines', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['pos_sale_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['measurement_unit_id']);
            $table->dropForeign(['tax_id']);
        });

        Schema::dropIfExists('app_pos_sale_lines');
    }
};
