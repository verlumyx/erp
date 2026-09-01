<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabecera del despacho: la salida física de mercancía hacia el cliente.
     *
     * No lleva moneda ni tasa. Sus importes no son de venta —el despacho no
     * factura— sino de costo de inventario, y el costo vive siempre en la
     * moneda en la que la empresa valora sus existencias.
     */
    public function up(): void
    {
        Schema::create('app_dispatches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->uuid('client_id');

            /**
             * Documento origen. No es un foreign key: el par
             * (`sourceable_type`, `sourceable_id`) forma una relación
             * polimórfica cuyo tipo guarda el alias del morph map, de modo que
             * mañana entren otros orígenes sin agregar una columna por cada
             * uno. Hoy solo `sales_order`. Su integridad la valida el Service.
             */
            $table->string('sourceable_type', 255)->nullable();
            $table->uuid('sourceable_id')->nullable();

            /** Dirección de entrega del cliente. */
            $table->uuid('client_address_id')->nullable();

            /** Bodega de origen: de aquí sale la mercancía. */
            $table->uuid('warehouse_id');

            /**
             * Ruta y parada del viaje. Sin foreign key hasta que exista el
             * módulo Rutas de Logística (`app_routes`, `app_route_stops`). No
             * entran en el morph: la ruta planifica el viaje, no origina el
             * despacho.
             */
            $table->uuid('route_id')->nullable();
            $table->uuid('route_stop_id')->nullable();

            $table->date('dispatch_date');
            /** Fecha efectiva de entrega; la escribe el registro de la entrega. */
            $table->date('delivery_date')->nullable();

            /** Quién lleva la mercancía y en qué. */
            $table->uuid('driver_id')->nullable();
            $table->string('vehicle_plate', 20)->nullable();
            $table->string('carrier', 150)->nullable();
            $table->string('tracking_number', 60)->nullable();

            $table->decimal('freight_amount', 18, 2)->default(0);

            /** Totales derivados de las líneas activas. */
            $table->decimal('total_quantity', 18, 4)->default(0);
            $table->decimal('total_weight', 18, 4)->default(0);
            $table->decimal('total_volume', 18, 4)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);

            /**
             * Cómo terminó el viaje. Es un eje distinto de `status`: el
             * documento se confirma y se cumple, la mercancía se entrega o se
             * rechaza.
             */
            $table->enum('delivery_status', [
                'pending', 'in_transit', 'delivered', 'partial_delivered', 'rejected', 'returned',
            ])->default('pending');

            /** Constancia de la entrega. */
            $table->string('received_by_name', 150)->nullable();
            $table->string('received_by_document', 30)->nullable();
            $table->string('signature_path', 500)->nullable();
            $table->string('evidence_path', 500)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('rejection_reason', 500)->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['draft', 'confirmed', 'completed', 'cancelled'])
                ->default('draft');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('client_id')
                ->references('id')
                ->on('app_clients')
                ->restrictOnDelete();

            $table->foreign('client_address_id')
                ->references('id')
                ->on('app_client_addresses')
                ->nullOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->restrictOnDelete();

            /** Foreign key: quién condujo el viaje (trazabilidad) */
            $table->foreign('driver_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /** Foreign key: quién registró el despacho (trazabilidad) */
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
            $table->index('client_id');
            $table->index(['sourceable_type', 'sourceable_id']);
            $table->index('dispatch_date');
            $table->index('route_id');
            $table->index('driver_id');
            $table->index('delivery_status');
            $table->index('warehouse_id');

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('app_dispatches', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['client_address_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['driver_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_dispatches');
    }
};
