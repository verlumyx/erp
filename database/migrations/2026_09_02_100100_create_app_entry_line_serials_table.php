<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las series que llegan en una línea de la entrada.
     *
     * Una serie identifica una unidad física, así que colgarlas de la línea en
     * un `json` impedía decir de qué lote sale cada una. Aquí cada serie es una
     * fila y apunta al lote que la trajo cuando el artículo también se controla
     * por lote.
     */
    public function up(): void
    {
        Schema::create('app_entry_line_serials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('entry_line_id');

            /** El lote del que sale esta unidad, si el artículo lleva lote. */
            $table->uuid('entry_line_lot_id')->nullable();

            $table->integer('line_number');

            /**
             * El número impreso en la unidad. Igual que el lote, se resuelve
             * contra `app_item_serials` al confirmar la entrada. Mide lo mismo
             * que `app_item_serials.serial_number`: 100, no 60.
             */
            $table->string('serial_number', 100);
            $table->uuid('serial_id')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('entry_line_id')
                ->references('id')
                ->on('app_entry_lines')
                ->cascadeOnDelete();

            $table->foreign('entry_line_lot_id')
                ->references('id')
                ->on('app_entry_line_lots')
                ->nullOnDelete();

            $table->foreign('serial_id')
                ->references('id')
                ->on('app_item_serials')
                ->nullOnDelete();

            $table->index('entry_line_id');
            $table->index('entry_line_lot_id');
            $table->index('company_id');
            $table->index('status');
            $table->index('serial_id');

            $table->unique(['entry_line_id', 'line_number'], 'app_entry_line_serials_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_entry_line_serials', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['entry_line_id']);
            $table->dropForeign(['entry_line_lot_id']);
            $table->dropForeign(['serial_id']);
        });

        Schema::dropIfExists('app_entry_line_serials');
    }
};
