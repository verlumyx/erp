<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las unidades con serie que nombra una línea del ajuste.
     *
     * Aquí no hay cantidad: una serie **es** una unidad. La fila solo dice qué
     * unidad concreta entra en el conteo —la que se encontró o la que falta, lo
     * decide la diferencia de la línea— y, cuando el artículo también lleva
     * lote, de qué fila de lote sale.
     *
     * La serie se elige del maestro: el ajuste corrige existencias, no estrena
     * unidades.
     */
    public function up(): void
    {
        Schema::create('app_adjustment_line_serials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('adjustment_line_id');

            $table->uuid('adjustment_line_lot_id')->nullable();

            $table->integer('line_number');

            $table->uuid('serial_id');

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('adjustment_line_id')
                ->references('id')
                ->on('app_adjustment_lines')
                ->cascadeOnDelete();

            $table->foreign('adjustment_line_lot_id')
                ->references('id')
                ->on('app_adjustment_line_lots')
                ->nullOnDelete();

            $table->foreign('serial_id')
                ->references('id')
                ->on('app_item_serials')
                ->restrictOnDelete();

            $table->index('adjustment_line_id');
            $table->index('adjustment_line_lot_id');
            $table->index('company_id');
            $table->index('status');
            $table->index('serial_id');

            $table->unique(['adjustment_line_id', 'line_number'], 'app_adjustment_line_serials_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_adjustment_line_serials', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['adjustment_line_id']);
            $table->dropForeign(['adjustment_line_lot_id']);
            $table->dropForeign(['serial_id']);
        });

        Schema::dropIfExists('app_adjustment_line_serials');
    }
};
