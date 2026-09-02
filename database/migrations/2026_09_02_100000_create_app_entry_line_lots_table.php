<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los lotes con los que llega una línea de la entrada.
     *
     * El lote deja de ser una columna de la línea porque una misma línea puede
     * llegar repartida en varios: el proveedor manda cien unidades en tres
     * cajas y cada caja trae su número y su vencimiento. Tabla de detalle de
     * una tabla de detalle: sin `code`, se edita desde la pantalla de la
     * entrada y se desactiva en vez de borrarse.
     */
    public function up(): void
    {
        Schema::create('app_entry_line_lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('entry_line_id');

            $table->integer('line_number');

            /**
             * El número impreso en la caja. La entrada **crea** el lote: al
             * confirmarla se busca en `app_item_lots` y se da de alta si no
             * existía, y el id resuelto se escribe aquí.
             */
            $table->string('lot_number', 60);
            $table->uuid('lot_id')->nullable();
            $table->date('expires_at')->nullable();

            /** Cuánto llegó en este lote, en la unidad de la línea. */
            $table->decimal('quantity', 18, 4);
            $table->decimal('base_quantity', 18, 4)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('entry_line_id')
                ->references('id')
                ->on('app_entry_lines')
                ->cascadeOnDelete();

            $table->foreign('lot_id')
                ->references('id')
                ->on('app_item_lots')
                ->nullOnDelete();

            $table->index('entry_line_id');
            $table->index('company_id');
            $table->index('status');
            $table->index('lot_id');

            $table->unique(['entry_line_id', 'line_number'], 'app_entry_line_lots_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_entry_line_lots', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['entry_line_id']);
            $table->dropForeign(['lot_id']);
        });

        Schema::dropIfExists('app_entry_line_lots');
    }
};
