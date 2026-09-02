<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de detalle: sin `code`, se edita desde la pantalla de la entrada.
     *
     * El lote y las series no viven aquí: son tablas de detalle propias
     * (`app_entry_line_lots`, `app_entry_line_serials`), porque una misma línea
     * puede llegar repartida en varios lotes. Las bases que nacieron con esas
     * columnas las sueltan en
     * `2026_09_02_100400_move_entry_line_traceability_to_detail_tables`.
     */
    public function up(): void
    {
        Schema::create('app_entry_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('entry_id');

            $table->integer('line_number');
            $table->uuid('item_id');
            $table->uuid('measurement_unit_id');

            /**
             * Línea origen: el mismo par polimórfico de la cabecera, apuntando
             * a la línea del documento que la origina (`purchase_order_line`).
             */
            $table->string('sourceable_type', 255)->nullable();
            $table->uuid('sourceable_id')->nullable();

            /** Ubicación donde se almacena. Vacía usa la de por defecto de la bodega. */
            $table->uuid('location_id')->nullable();

            $table->decimal('quantity', 18, 4);
            $table->decimal('base_quantity', 18, 4);

            /** Costo unitario que factura el proveedor, en la unidad de la línea. */
            $table->decimal('unit_price', 18, 6);

            $table->decimal('discount_percent', 7, 4)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);

            $table->uuid('tax_id')->nullable();
            $table->decimal('tax_percent', 7, 4)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('withholding_percent', 7, 4)->default(0);
            $table->decimal('withholding_amount', 18, 2)->default(0);

            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            /** Lo aceptado y lo rechazado. Solo lo aceptado llega al kardex. */
            $table->decimal('received_quantity', 18, 4)->default(0);
            $table->decimal('rejected_quantity', 18, 4)->default(0);

            /**
             * Costo por **unidad base** antes de prorrateos, y el mismo costo ya
             * con su parte del flete y de los otros gastos. Al kardex entra
             * `landed_cost`, no `unit_price`.
             */
            $table->decimal('unit_cost', 18, 6)->default(0);
            $table->decimal('landed_cost', 18, 6)->default(0);

            $table->string('rejection_reason', 500)->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('entry_id')
                ->references('id')
                ->on('app_entries')
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

            $table->index('entry_id');
            $table->index('item_id');
            $table->index('company_id');
            $table->index('status');
            $table->index(['sourceable_type', 'sourceable_id']);

            $table->unique(['entry_id', 'line_number'], 'app_entry_lines_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_entry_lines', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['entry_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['measurement_unit_id']);
            $table->dropForeign(['location_id']);
            $table->dropForeign(['tax_id']);
        });

        Schema::dropIfExists('app_entry_lines');
    }
};
