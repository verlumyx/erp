<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabecera del traslado: mercancía que se mueve entre bodegas de la misma
     * empresa.
     *
     * No lleva moneda ni tasa, y por la misma razón que el despacho: el
     * traslado no factura. Más aún, **no cambia el valor total del inventario**
     * —solo su ubicación—, así que el único importe que guarda es el costo con
     * el que la mercancía viaja, y ese vive siempre en la moneda en la que la
     * empresa valora sus existencias.
     */
    public function up(): void
    {
        Schema::create('app_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            /** De dónde sale y a dónde llega. Nunca pueden ser la misma. */
            $table->uuid('origin_warehouse_id');
            $table->uuid('destination_warehouse_id');

            /**
             * Bodega de tránsito. Su presencia es la que decide el número de
             * pasos: con ella el traslado sale hoy y llega después —la
             * mercancía vive en tránsito mientras viaja—, y sin ella los dos
             * movimientos son simultáneos.
             */
            $table->uuid('transit_warehouse_id')->nullable();

            $table->date('transfer_date');
            $table->date('expected_date')->nullable();
            /** Fecha efectiva de llegada; la escribe el registro de la recepción. */
            $table->date('received_date')->nullable();

            $table->enum('reason', [
                'restock', 'rebalance', 'damaged', 'quarantine', 'other',
            ])->default('restock');
            $table->string('reason_detail', 500)->nullable();

            /** Quién lleva la mercancía y en qué. */
            $table->uuid('driver_id')->nullable();
            $table->string('vehicle_plate', 20)->nullable();

            /**
             * Ruta del viaje. Sin foreign key hasta que exista el módulo Rutas
             * de Logística (`app_routes`).
             */
            $table->uuid('route_id')->nullable();

            /** Totales derivados de las líneas activas. */
            $table->decimal('total_quantity', 18, 4)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);

            /**
             * Dónde está la mercancía. Es un eje distinto de `status`: el
             * documento se confirma y se cumple, la mercancía sale, viaja y
             * llega —entera o a medias—.
             */
            $table->enum('transfer_status', [
                'pending', 'in_transit', 'received', 'partial_received',
            ])->default('pending');

            /** Quién despachó desde origen y quién recibió en destino. */
            $table->uuid('sent_by')->nullable();
            $table->uuid('received_by')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();

            /**
             * `partial` no se elige: lo escribe la recepción cuando llega menos
             * de lo que salió.
             */
            $table->enum('status', ['draft', 'confirmed', 'partial', 'completed', 'cancelled'])
                ->default('draft');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('origin_warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->restrictOnDelete();

            $table->foreign('destination_warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->restrictOnDelete();

            $table->foreign('transit_warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->restrictOnDelete();

            /** Foreign key: quién condujo el viaje (trazabilidad) */
            $table->foreign('driver_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /** Foreign key: quién despachó desde la bodega de origen */
            $table->foreign('sent_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /** Foreign key: quién recibió en la bodega de destino */
            $table->foreign('received_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /** Foreign key: quién registró el traslado (trazabilidad) */
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices base
            $table->index('company_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');

            // Índices propios del módulo
            $table->index('origin_warehouse_id');
            $table->index('destination_warehouse_id');
            $table->index('transfer_date');
            $table->index('transfer_status');
            $table->index('driver_id');

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('app_transfers', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['origin_warehouse_id']);
            $table->dropForeign(['destination_warehouse_id']);
            $table->dropForeign(['transit_warehouse_id']);
            $table->dropForeign(['driver_id']);
            $table->dropForeign(['sent_by']);
            $table->dropForeign(['received_by']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_transfers');
    }
};
