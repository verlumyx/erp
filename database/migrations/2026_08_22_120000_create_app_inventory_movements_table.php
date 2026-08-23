<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->dateTime('movement_date');
            $table->enum('type', [
                'in',
                'out',
                'transfer_in',
                'transfer_out',
                'adjustment_in',
                'adjustment_out',
            ]);

            /**
             * Documento origen (relación polimórfica manual): `purchase_invoice`,
             * `sales_invoice`, `dispatch`, `transfer`, `entry`, `adjustment`,
             * `purchase_return`, `sales_return`. Va como `string` y no como
             * `enum` porque la lista crece con cada módulo de Logística.
             */
            $table->string('origin_type', 50);
            $table->uuid('origin_id');
            $table->uuid('origin_line_id')->nullable();

            $table->uuid('item_id');
            $table->uuid('warehouse_id');
            $table->uuid('location_id')->nullable();
            $table->uuid('lot_id')->nullable();
            $table->uuid('serial_id')->nullable();

            /** Siempre positiva: el signo lo determina `type`. */
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 6)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);

            /**
             * Saldo del artículo en la bodega **después** del movimiento. Se
             * congela al registrarlo para que el kardex sea auditable sin
             * recalcular toda la historia.
             */
            $table->decimal('balance_quantity', 18, 4)->default(0);
            $table->decimal('balance_cost', 18, 6)->default(0);
            $table->decimal('balance_value', 18, 2)->default(0);

            /** Movimiento original que esta fila revierte (anulaciones). */
            $table->uuid('reversal_of_id')->nullable();

            /**
             * El kardex no se desactiva: una fila queda `reversed` cuando su
             * contrapartida ya existe. El contenido contable —cantidad, costo
             * y saldos— nunca cambia.
             */
            $table->enum('status', ['active', 'reversed'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->uuid('created_by')->nullable();
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

            $table->foreign('serial_id')
                ->references('id')
                ->on('app_item_serials')
                ->restrictOnDelete();

            /** Foreign key: quién registró el movimiento (trazabilidad) */
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices
            $table->index(['item_id', 'warehouse_id', 'movement_date']);
            $table->index(['origin_type', 'origin_id']);
            $table->index('movement_date');
            $table->index('lot_id');
            $table->index('serial_id');
            $table->index('type');
            $table->index('reversal_of_id');
            $table->index('company_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');

            $table->unique(['company_id', 'code']);
        });

        /**
         * La contrapartida apunta al movimiento original, así que la clave
         * foránea es contra la propia tabla. Va aparte del `create()` porque
         * PostgreSQL declara la primary key al final del `CREATE TABLE`: dentro
         * del mismo bloque, `id` todavía no es única cuando se agrega la
         * restricción.
         */
        Schema::table('app_inventory_movements', function (Blueprint $table) {
            $table->foreign('reversal_of_id')
                ->references('id')
                ->on('app_inventory_movements')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['location_id']);
            $table->dropForeign(['lot_id']);
            $table->dropForeign(['serial_id']);
            $table->dropForeign(['reversal_of_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_inventory_movements');
    }
};
