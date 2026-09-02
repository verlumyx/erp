<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de detalle: sin `code`, se edita desde la pantalla del despacho.
     *
     * Los importes de la línea son informativos —el despacho no factura—: se
     * copian del pedido para que la guía enseñe lo mismo que el cliente pidió.
     * Lo que sí mueve inventario es `quantity` valorada a `unit_cost`.
     *
     * El lote y la serie no viven aquí: son tablas de detalle propias
     * (`app_dispatch_line_lots`, `app_dispatch_line_serials`), porque una misma
     * línea puede salir repartida en varios lotes y llevar varias series. Las
     * bases que nacieron con esas columnas las sueltan en
     * `2026_09_02_100500_move_dispatch_line_traceability_to_detail_tables`.
     */
    public function up(): void
    {
        Schema::create('app_dispatch_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('dispatch_id');

            $table->integer('line_number');
            $table->uuid('item_id');
            $table->uuid('measurement_unit_id');

            /**
             * Línea origen (`sales_order_line`). Sin foreign key, igual que en
             * la cabecera: es el mismo morph. El Service comprueba que sea del
             * documento origen del despacho.
             */
            $table->string('sourceable_type', 255)->nullable();
            $table->uuid('sourceable_id')->nullable();

            /** De dónde se toma la mercancía dentro de la bodega. */
            $table->uuid('location_id')->nullable();

            $table->decimal('quantity', 18, 4);
            $table->decimal('base_quantity', 18, 4);

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

            /** Lo que el cliente recibió y lo que devolvió en el mismo viaje. */
            $table->decimal('delivered_quantity', 18, 4)->default(0);
            $table->decimal('returned_quantity', 18, 4)->default(0);

            /**
             * Costo con el que la mercancía salió. Lo resuelve el backend: en
             * borrador con el promedio vigente y al confirmar con el que el
             * kardex usó de verdad.
             */
            $table->decimal('unit_cost', 18, 6)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('dispatch_id')
                ->references('id')
                ->on('app_dispatches')
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

            $table->foreign('tax_id')
                ->references('id')
                ->on('app_taxes')
                ->nullOnDelete();

            $table->index('dispatch_id');
            $table->index('item_id');
            $table->index('company_id');
            $table->index('status');
            $table->index(['sourceable_type', 'sourceable_id'], 'app_dispatch_lines_source_index');

            $table->unique(['dispatch_id', 'line_number'], 'app_dispatch_lines_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_dispatch_lines', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['dispatch_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['measurement_unit_id']);
            $table->dropForeign(['location_id']);
            $table->dropForeign(['tax_id']);
        });

        Schema::dropIfExists('app_dispatch_lines');
    }
};
