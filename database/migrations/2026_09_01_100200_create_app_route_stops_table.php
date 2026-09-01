<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de detalle: la ejecución de la ruta en una fecha concreta.
     *
     * Es el otro eje de `app_route_clients`. Aquella dice a quién le toca; esta
     * dice qué pasó el martes: a qué hora se llegó, si se pudo entregar y, si
     * no, por qué. Por eso lleva `stop_date`, horas reales y coordenadas, y por
     * eso su `stop_status` es un eje aparte del `status` de la fila —una parada
     * fallida sigue siendo una parada activa del día—.
     */
    public function up(): void
    {
        Schema::create('app_route_stops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('route_id');

            $table->uuid('client_id');
            $table->uuid('client_address_id')->nullable();

            $table->date('stop_date');
            $table->integer('sequence')->default(0);

            /** Lo que se planificó y lo que de verdad ocurrió. */
            $table->time('estimated_arrival')->nullable();
            $table->dateTime('actual_arrival')->nullable();
            $table->dateTime('actual_departure')->nullable();

            $table->enum('stop_status', ['pending', 'arrived', 'completed', 'skipped', 'failed'])
                ->default('pending');

            /** Por qué no se visitó: cerrado, no recibió, dirección errada. */
            $table->string('skip_reason', 500)->nullable();

            /** Dónde estaba el vehículo cuando se registró la visita. */
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('route_id')
                ->references('id')
                ->on('app_routes')
                ->cascadeOnDelete();

            $table->foreign('client_id')
                ->references('id')
                ->on('app_clients')
                ->restrictOnDelete();

            $table->foreign('client_address_id')
                ->references('id')
                ->on('app_client_addresses')
                ->nullOnDelete();

            $table->index('company_id');
            $table->index('status');
            $table->index('route_id');
            $table->index('stop_date');
            $table->index('client_id');
            $table->index('stop_status');
            $table->index('sequence');

            /**
             * Un cliente se visita una vez por ruta y por día. Replanificar la
             * misma fecha reescribe la parada, no la duplica.
             */
            $table->unique(
                ['route_id', 'stop_date', 'client_id'],
                'app_route_stops_day_client_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('app_route_stops', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['route_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['client_address_id']);
        });

        Schema::dropIfExists('app_route_stops');
    }
};
