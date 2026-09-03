<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de detalle: sin `code`, se edita desde la pantalla del ajuste.
     */
    public function up(): void
    {
        Schema::create('app_adjustment_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('adjustment_id');

            $table->integer('line_number');
            $table->uuid('item_id');
            $table->uuid('measurement_unit_id');

            /** Dónde se contó. Vacía usa la ubicación por defecto de la bodega. */
            $table->uuid('location_id')->nullable();

            /**
             * Existencia según el sistema al capturar la línea, y lo que se
             * contó físicamente. Ambas en la unidad de la línea. El lote y la
             * serie no están aquí: una misma línea puede contar varios lotes y
             * nombrar varias unidades, así que viven en
             * `app_adjustment_line_lots` y `app_adjustment_line_serials`.
             */
            $table->decimal('system_quantity', 18, 4)->default(0);
            $table->decimal('counted_quantity', 18, 4)->default(0);

            /** `counted - system`. Positivo es sobrante, negativo faltante. */
            $table->decimal('difference_quantity', 18, 4)->default(0);

            /** La diferencia llevada a la unidad base, que es la del kardex. */
            $table->decimal('base_quantity', 18, 4)->default(0);

            $table->enum('movement_type', ['adjustment_in', 'adjustment_out']);

            /** Costo con el que se valora el ajuste, por unidad base. */
            $table->decimal('unit_cost', 18, 6)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);

            /** Motivo específico de la línea, cuando difiere del de la cabecera. */
            $table->string('reason', 500)->nullable();

            $table->uuid('counted_by')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('adjustment_id')
                ->references('id')
                ->on('app_adjustments')
                ->cascadeOnDelete();

            $table->foreign('item_id')
                ->references('id')
                ->on('app_items')
                ->restrictOnDelete();

            $table->foreign('measurement_unit_id')
                ->references('id')
                ->on('app_measurement_units')
                ->restrictOnDelete();

            $table->foreign('location_id')
                ->references('id')
                ->on('app_warehouse_locations')
                ->nullOnDelete();

            /** Foreign key: quién contó físicamente la línea (trazabilidad) */
            $table->foreign('counted_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('adjustment_id');
            $table->index('item_id');
            $table->index('company_id');
            $table->index('status');

            $table->unique(['adjustment_id', 'line_number'], 'app_adjustment_lines_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_adjustment_lines', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['adjustment_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['measurement_unit_id']);
            $table->dropForeign(['location_id']);
            $table->dropForeign(['counted_by']);
        });

        Schema::dropIfExists('app_adjustment_lines');
    }
};
