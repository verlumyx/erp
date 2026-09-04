<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El reparto por lote de una línea del expediente.
     *
     * El gasto se reparte y el costo se reexpresa por lote porque así es como
     * el kardex lo tiene escrito: la entrada asentó un movimiento por cada
     * caja, cada uno con su cantidad y su costo. Dos lotes de la misma línea
     * pueden tener distinto saldo vivo, y repartir por la línea capitalizaría
     * en un lote gasto que le tocaba al otro.
     */
    public function up(): void
    {
        Schema::create('app_import_line_lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('import_line_id');

            $table->integer('line_number');

            /** De aquí sale todo: la fila de lote de la línea de entrada. */
            $table->uuid('entry_line_lot_id');
            $table->uuid('lot_id');

            /** Lo aceptado de ese lote, y lo que de eso sigue en existencia. */
            $table->decimal('base_quantity', 18, 4)->default(0);
            $table->decimal('remaining_quantity', 18, 4)->default(0);

            $table->decimal('allocation_base', 18, 4)->default(0);
            $table->decimal('allocated_amount', 18, 2)->default(0);

            $table->decimal('unit_delta', 18, 6)->default(0);
            $table->decimal('new_unit_cost', 18, 6)->default(0);

            $table->decimal('capitalized_amount', 18, 2)->default(0);
            $table->decimal('variance_amount', 18, 2)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('import_line_id')
                ->references('id')
                ->on('app_import_lines')
                ->cascadeOnDelete();

            $table->foreign('entry_line_lot_id')
                ->references('id')
                ->on('app_entry_line_lots')
                ->restrictOnDelete();

            $table->foreign('lot_id')
                ->references('id')
                ->on('app_item_lots')
                ->restrictOnDelete();

            $table->index('import_line_id');
            $table->index('entry_line_lot_id');
            $table->index('lot_id');
            $table->index('company_id');
            $table->index('status');

            $table->unique(['import_line_id', 'line_number'], 'app_import_line_lots_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_import_line_lots', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['import_line_id']);
            $table->dropForeign(['entry_line_lot_id']);
            $table->dropForeign(['lot_id']);
        });

        Schema::dropIfExists('app_import_line_lots');
    }
};
