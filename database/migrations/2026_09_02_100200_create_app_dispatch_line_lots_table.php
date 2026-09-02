<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los lotes de los que sale una línea del despacho.
     *
     * A diferencia de la entrada, el despacho **consume** trazabilidad que ya
     * existe: el lote se elige del maestro, así que `lot_id` es obligatorio y
     * está protegido contra el borrado. Tampoco lleva número ni vencimiento:
     * esos datos son del lote, no del despacho.
     */
    public function up(): void
    {
        Schema::create('app_dispatch_line_lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('dispatch_line_id');

            $table->integer('line_number');

            $table->uuid('lot_id');

            /** Cuánto sale de este lote, en la unidad de la línea. */
            $table->decimal('quantity', 18, 4);
            $table->decimal('base_quantity', 18, 4)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('dispatch_line_id')
                ->references('id')
                ->on('app_dispatch_lines')
                ->cascadeOnDelete();

            $table->foreign('lot_id')
                ->references('id')
                ->on('app_item_lots')
                ->restrictOnDelete();

            $table->index('dispatch_line_id');
            $table->index('company_id');
            $table->index('status');
            $table->index('lot_id');

            $table->unique(['dispatch_line_id', 'line_number'], 'app_dispatch_line_lots_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_dispatch_line_lots', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['dispatch_line_id']);
            $table->dropForeign(['lot_id']);
        });

        Schema::dropIfExists('app_dispatch_line_lots');
    }
};
