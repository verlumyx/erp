<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los lotes que se contaron en una línea del ajuste.
     *
     * Una fila es una mini-línea: dice cuánto se encontró de ese lote y guarda,
     * resuelta por el backend, la existencia contra la que se compara. Por eso
     * repite las columnas de cálculo de la línea —el kardex escribe un
     * movimiento por lote, y cada uno tiene su propia diferencia y su propio
     * costo—.
     *
     * El ajuste **corrige** trazabilidad que ya existe: el lote se elige del
     * maestro, así que `lot_id` es obligatorio y está protegido contra el
     * borrado.
     */
    public function up(): void
    {
        Schema::create('app_adjustment_line_lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('adjustment_line_id');

            $table->integer('line_number');

            $table->uuid('lot_id');

            /** Lo que se encontró de este lote, en la unidad de la línea. */
            $table->decimal('counted_quantity', 18, 4)->default(0);

            /** Lo que el sistema decía de este lote, en la unidad de la línea. */
            $table->decimal('system_quantity', 18, 4)->default(0);

            /** `counted - system`. Positivo es sobrante, negativo faltante. */
            $table->decimal('difference_quantity', 18, 4)->default(0);

            /** La diferencia llevada a la unidad base, que es la del kardex. */
            $table->decimal('base_quantity', 18, 4)->default(0);

            $table->enum('movement_type', ['adjustment_in', 'adjustment_out']);

            $table->decimal('unit_cost', 18, 6)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('adjustment_line_id')
                ->references('id')
                ->on('app_adjustment_lines')
                ->cascadeOnDelete();

            $table->foreign('lot_id')
                ->references('id')
                ->on('app_item_lots')
                ->restrictOnDelete();

            $table->index('adjustment_line_id');
            $table->index('company_id');
            $table->index('status');
            $table->index('lot_id');

            $table->unique(['adjustment_line_id', 'line_number'], 'app_adjustment_line_lots_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_adjustment_line_lots', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['adjustment_line_id']);
            $table->dropForeign(['lot_id']);
        });

        Schema::dropIfExists('app_adjustment_line_lots');
    }
};
