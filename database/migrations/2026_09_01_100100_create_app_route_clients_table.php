<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de detalle: los clientes fijos de la ruta, en el orden en que se
     * visitan. Es la **plantilla** desde la que se generan las paradas de cada
     * fecha, no la ejecución: aquí no hay día ni hora, solo a quién le toca.
     *
     * Sin `code`: se edita desde la pantalla de la ruta y se identifica por su
     * padre más el cliente.
     */
    public function up(): void
    {
        Schema::create('app_route_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('route_id');

            $table->uuid('client_id');
            /** Cuál de sus direcciones se visita. Vacía: la que tenga por defecto. */
            $table->uuid('client_address_id')->nullable();

            $table->integer('sequence')->default(0);

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
                ->cascadeOnDelete();

            $table->foreign('client_address_id')
                ->references('id')
                ->on('app_client_addresses')
                ->nullOnDelete();

            $table->index('route_id');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('status');

            /**
             * Un cliente no se repite en la misma ruta con la misma dirección.
             * Con la dirección vacía la base no puede impedirlo —en Postgres
             * dos NULL son distintos—, así que el Request también lo valida.
             */
            $table->unique(
                ['route_id', 'client_id', 'client_address_id'],
                'app_route_clients_client_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('app_route_clients', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['route_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['client_address_id']);
        });

        Schema::dropIfExists('app_route_clients');
    }
};
