<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_item_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('item_id');
            $table->uuid('warehouse_id');
            $table->uuid('location_id');
            $table->uuid('lot_id')->nullable();
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('reserved_quantity', 18, 4)->default(0);
            $table->decimal('incoming_quantity', 18, 4)->default(0);
            $table->decimal('available_quantity', 18, 4)->default(0);
            $table->decimal('average_cost', 18, 6)->default(0);
            $table->decimal('total_value', 18, 2)->default(0);
            $table->timestamp('last_movement_at')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('item_id')
                ->references('id')
                ->on('app_items')
                ->restrictOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->restrictOnDelete();

            $table->foreign('location_id')
                ->references('id')
                ->on('app_warehouse_locations')
                ->restrictOnDelete();

            $table->foreign('lot_id')
                ->references('id')
                ->on('app_item_lots')
                ->restrictOnDelete();

            // Índices
            $table->index('company_id');
            $table->index('item_id');
            $table->index('warehouse_id');
            $table->index('location_id');
            $table->index('lot_id');
            $table->index('quantity');
            $table->index('status');
            $table->index('created_at');
        });

        /**
         * Un solo saldo por artículo/bodega/ubicación/lote.
         *
         * Va como par de índices parciales y no como unique() de Blueprint
         * porque en SQL un NULL nunca es igual a otro NULL: con `lot_id` nulo
         * —el caso de todo artículo que no se controla por lote— la restricción
         * de cinco columnas no impediría filas duplicadas. Ambos motores del
         * proyecto (PostgreSQL y el SQLite de los tests) soportan el WHERE.
         */
        DB::statement(
            'CREATE UNIQUE INDEX app_item_stocks_balance_lot_unique
             ON app_item_stocks (company_id, item_id, warehouse_id, location_id, lot_id)
             WHERE lot_id IS NOT NULL'
        );

        DB::statement(
            'CREATE UNIQUE INDEX app_item_stocks_balance_no_lot_unique
             ON app_item_stocks (company_id, item_id, warehouse_id, location_id)
             WHERE lot_id IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS app_item_stocks_balance_lot_unique');
        DB::statement('DROP INDEX IF EXISTS app_item_stocks_balance_no_lot_unique');

        Schema::table('app_item_stocks', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['location_id']);
            $table->dropForeign(['lot_id']);
        });

        Schema::dropIfExists('app_item_stocks');
    }
};
