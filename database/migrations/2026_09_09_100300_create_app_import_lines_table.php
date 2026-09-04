<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los ítems del expediente: lo que absorbe el gasto.
     *
     * No se capturan. Cada línea viva de cada entrada asociada que mueva
     * existencia entra aquí con su cantidad aceptada y su costo de entrada, y
     * lo único editable de la sección es `status`: sacar una línea del reparto
     * cuando ese ítem no viajó en ese embarque.
     */
    public function up(): void
    {
        Schema::create('app_import_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('import_id');

            $table->integer('line_number');

            /** De aquí sale todo: la línea de la entrada que se está costeando. */
            $table->uuid('entry_line_id');

            $table->uuid('item_id');
            $table->uuid('measurement_unit_id');
            $table->uuid('location_id')->nullable();

            /** Lo aceptado en la entrada, en unidad base. Es lo que absorbe gasto. */
            $table->decimal('base_quantity', 18, 4)->default(0);

            /** De eso, lo que sigue en existencia al costear. */
            $table->decimal('remaining_quantity', 18, 4)->default(0);

            /** Costo con el que entró: el `landed_cost` de la línea de entrada. */
            $table->decimal('unit_cost', 18, 6)->default(0);
            $table->decimal('base_value', 18, 2)->default(0);

            /** El número con el que reparte, según `allocation_method`. */
            $table->decimal('allocation_base', 18, 4)->default(0);
            $table->decimal('allocated_amount', 18, 2)->default(0);

            /** Lo que sube cada unidad, y el costo que el ajuste va a escribir. */
            $table->decimal('unit_delta', 18, 6)->default(0);
            $table->decimal('new_unit_cost', 18, 6)->default(0);

            /** Lo que llega al inventario, y lo que se queda en gasto. */
            $table->decimal('capitalized_amount', 18, 2)->default(0);
            $table->decimal('variance_amount', 18, 2)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('import_id')
                ->references('id')
                ->on('app_imports')
                ->cascadeOnDelete();

            $table->foreign('entry_line_id')
                ->references('id')
                ->on('app_entry_lines')
                ->restrictOnDelete();

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

            $table->index('import_id');
            $table->index('entry_line_id');
            $table->index('item_id');
            $table->index('company_id');
            $table->index('status');

            $table->unique(['import_id', 'line_number'], 'app_import_lines_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_import_lines', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['import_id']);
            $table->dropForeign(['entry_line_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['measurement_unit_id']);
            $table->dropForeign(['location_id']);
        });

        Schema::dropIfExists('app_import_lines');
    }
};
