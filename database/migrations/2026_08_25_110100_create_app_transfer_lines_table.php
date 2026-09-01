<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de detalle: sin `code`, se edita desde la pantalla del traslado.
     *
     * El traslado no pone precio a nada: mueve mercancía que ya tiene un costo.
     * Las columnas de importe de la estructura común de líneas se conservan
     * —son las mismas en toda tabla `*_lines`— pero las llena ese costo:
     * `unit_price` es el mismo `unit_cost` expresado en la unidad de la línea,
     * y `subtotal` y `total` son el valor que viaja. Descuentos, impuestos y
     * retenciones se quedan en cero, porque entre bodegas propias no hay nada
     * que descontar ni que gravar.
     */
    public function up(): void
    {
        Schema::create('app_transfer_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('transfer_id');

            $table->integer('line_number');
            $table->uuid('item_id');
            $table->uuid('measurement_unit_id');

            /** De qué sitio de la bodega sale y en cuál de la otra se guarda. */
            $table->uuid('origin_location_id')->nullable();
            $table->uuid('destination_location_id')->nullable();

            $table->uuid('lot_id')->nullable();
            $table->uuid('serial_id')->nullable();

            $table->decimal('quantity', 18, 4);
            $table->decimal('base_quantity', 18, 4);

            /** El costo de la línea visto en su unidad. No es un precio de venta. */
            $table->decimal('unit_price', 18, 6)->default(0);

            $table->decimal('discount_percent', 7, 4)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);

            $table->uuid('tax_id')->nullable();
            $table->decimal('tax_percent', 7, 4)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('withholding_percent', 7, 4)->default(0);
            $table->decimal('withholding_amount', 18, 2)->default(0);

            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            /**
             * Lo que salió, lo que llegó y lo que se perdió por el camino. Las
             * tres van en la unidad de la línea, y la diferencia es una resta:
             * no se captura para que los tres números no puedan contradecirse.
             */
            $table->decimal('sent_quantity', 18, 4)->default(0);
            $table->decimal('received_quantity', 18, 4)->default(0);
            $table->decimal('difference_quantity', 18, 4)->default(0);

            /**
             * Costo con el que la mercancía viaja, por **unidad base**. Lo
             * resuelve el kardex al sacarla del origen, y con él —no con el
             * promedio del destino— entra en la bodega que la recibe.
             */
            $table->decimal('unit_cost', 18, 6)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('transfer_id')
                ->references('id')
                ->on('app_transfers')
                ->cascadeOnDelete();

            $table->foreign('item_id')
                ->references('id')
                ->on('app_items')
                ->restrictOnDelete();

            $table->foreign('measurement_unit_id')
                ->references('id')
                ->on('app_measurement_units')
                ->restrictOnDelete();

            $table->foreign('origin_location_id')
                ->references('id')
                ->on('app_warehouse_locations')
                ->nullOnDelete();

            $table->foreign('destination_location_id')
                ->references('id')
                ->on('app_warehouse_locations')
                ->nullOnDelete();

            $table->foreign('lot_id')
                ->references('id')
                ->on('app_item_lots')
                ->restrictOnDelete();

            $table->foreign('serial_id')
                ->references('id')
                ->on('app_item_serials')
                ->restrictOnDelete();

            $table->foreign('tax_id')
                ->references('id')
                ->on('app_taxes')
                ->nullOnDelete();

            $table->index('transfer_id');
            $table->index('item_id');
            $table->index('company_id');
            $table->index('status');

            $table->unique(['transfer_id', 'line_number'], 'app_transfer_lines_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_transfer_lines', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['transfer_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['measurement_unit_id']);
            $table->dropForeign(['origin_location_id']);
            $table->dropForeign(['destination_location_id']);
            $table->dropForeign(['lot_id']);
            $table->dropForeign(['serial_id']);
            $table->dropForeign(['tax_id']);
        });

        Schema::dropIfExists('app_transfer_lines');
    }
};
