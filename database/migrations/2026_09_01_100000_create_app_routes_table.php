<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabecera de la ruta: el recorrido que agrupa clientes y ordena las
     * visitas para entregar y para cobrar.
     *
     * Es un **maestro**, no un documento: no se confirma ni se anula, se activa
     * y se desactiva. Por eso tampoco toca el kardex —la ruta planifica el
     * viaje, la mercancía la mueve el despacho— y por eso no lleva moneda ni
     * tasa: el único número con unidades que guarda es la capacidad del
     * vehículo, y esa se mide en peso y en volumen.
     */
    public function up(): void
    {
        Schema::create('app_routes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->string('name', 150);
            $table->text('description')->nullable();

            /**
             * Para qué se recorre. `mixed` es la ruta que entrega y cobra en la
             * misma visita, que es lo habitual en distribución.
             */
            $table->enum('type', ['delivery', 'collection', 'sales', 'mixed'])
                ->default('delivery');

            /** De qué bodega sale la carga del día. */
            $table->uuid('warehouse_id')->nullable();

            /** Quién conduce y quién vende: no siempre son la misma persona. */
            $table->uuid('driver_id')->nullable();
            $table->uuid('salesperson_id')->nullable();

            $table->string('vehicle_plate', 20)->nullable();

            /**
             * Lo que el vehículo aguanta. En cero significa «sin declarar»: la
             * planificación no puede comparar contra un límite que nadie puso.
             */
            $table->decimal('vehicle_capacity_weight', 18, 4)->default(0);
            $table->decimal('vehicle_capacity_volume', 18, 4)->default(0);

            /** Cada cuánto se recorre y, si es semanal, qué días. */
            $table->enum('frequency', ['daily', 'weekly', 'biweekly', 'monthly', 'on_demand'])
                ->default('weekly');
            $table->json('weekdays')->nullable();

            $table->string('zone', 100)->nullable();
            $table->string('city', 100)->nullable();

            /** Lo que se espera del recorrido, para contrastarlo con lo real. */
            $table->integer('estimated_duration_minutes')->default(0);
            $table->decimal('estimated_distance_km', 10, 2)->default(0);

            $table->text('notes')->nullable();

            /** Maestro: se desactiva, nunca se borra. */
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->nullOnDelete();

            $table->foreign('driver_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('salesperson_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices base
            $table->index('company_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');
            $table->index('name');

            // Índices propios del módulo
            $table->index('type');
            $table->index('driver_id');
            $table->index('warehouse_id');
            $table->index('zone');

            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('app_routes', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['driver_id']);
            $table->dropForeign(['salesperson_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_routes');
    }
};
