<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de detalle: sin `code`, se edita desde la pantalla de la devolución.
     */
    public function up(): void
    {
        Schema::create('app_purchase_return_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('purchase_return_id');

            $table->integer('line_number');
            $table->uuid('item_id');
            $table->uuid('measurement_unit_id');

            /** Línea facturada: es la que limita cuánto se puede devolver. */
            $table->uuid('purchase_invoice_line_id')->nullable();

            /** Lote y serie devueltos: los mismos que se recibieron. */
            $table->uuid('lot_id')->nullable();
            $table->uuid('serial_id')->nullable();

            /** Ubicación de la que se toma. Vacía usa la de por defecto de la bodega. */
            $table->uuid('location_id')->nullable();

            $table->decimal('quantity', 18, 4);
            $table->decimal('base_quantity', 18, 4);
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

            /** Motivo específico de la línea; sin él manda el de la cabecera. */
            $table->enum('reason', ['damaged', 'wrong_item', 'expired', 'excess', 'quality', 'other'])
                ->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('purchase_return_id')
                ->references('id')
                ->on('app_purchase_returns')
                ->cascadeOnDelete();

            $table->foreign('item_id')
                ->references('id')
                ->on('app_items')
                ->restrictOnDelete();

            $table->foreign('measurement_unit_id')
                ->references('id')
                ->on('app_measurement_units')
                ->restrictOnDelete();

            $table->foreign('purchase_invoice_line_id')
                ->references('id')
                ->on('app_purchase_invoice_lines')
                ->restrictOnDelete();

            $table->foreign('lot_id')
                ->references('id')
                ->on('app_item_lots')
                ->restrictOnDelete();

            $table->foreign('serial_id')
                ->references('id')
                ->on('app_item_serials')
                ->restrictOnDelete();

            $table->foreign('location_id')
                ->references('id')
                ->on('app_warehouse_locations')
                ->nullOnDelete();

            $table->foreign('tax_id')
                ->references('id')
                ->on('app_taxes')
                ->nullOnDelete();

            $table->index('purchase_return_id');
            $table->index('item_id');
            $table->index('company_id');
            $table->index('status');
            $table->index('purchase_invoice_line_id');

            $table->unique(['purchase_return_id', 'line_number'], 'app_purchase_return_lines_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_purchase_return_lines', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['purchase_return_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['measurement_unit_id']);
            $table->dropForeign(['purchase_invoice_line_id']);
            $table->dropForeign(['lot_id']);
            $table->dropForeign(['serial_id']);
            $table->dropForeign(['location_id']);
            $table->dropForeign(['tax_id']);
        });

        Schema::dropIfExists('app_purchase_return_lines');
    }
};
