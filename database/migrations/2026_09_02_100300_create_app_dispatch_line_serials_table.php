<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las series que salen en una línea del despacho.
     *
     * Cada fila es una unidad física que se va, elegida del maestro: por eso
     * `serial_id` es obligatorio. Cuando el artículo también lleva lote, la
     * serie apunta a la fila de lote de la que sale.
     */
    public function up(): void
    {
        Schema::create('app_dispatch_line_serials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('dispatch_line_id');

            $table->uuid('dispatch_line_lot_id')->nullable();

            $table->integer('line_number');

            $table->uuid('serial_id');

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('dispatch_line_id')
                ->references('id')
                ->on('app_dispatch_lines')
                ->cascadeOnDelete();

            $table->foreign('dispatch_line_lot_id')
                ->references('id')
                ->on('app_dispatch_line_lots')
                ->nullOnDelete();

            $table->foreign('serial_id')
                ->references('id')
                ->on('app_item_serials')
                ->restrictOnDelete();

            $table->index('dispatch_line_id');
            $table->index('dispatch_line_lot_id');
            $table->index('company_id');
            $table->index('status');
            $table->index('serial_id');

            $table->unique(['dispatch_line_id', 'line_number'], 'app_dispatch_line_serials_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_dispatch_line_serials', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['dispatch_line_id']);
            $table->dropForeign(['dispatch_line_lot_id']);
            $table->dropForeign(['serial_id']);
        });

        Schema::dropIfExists('app_dispatch_line_serials');
    }
};
