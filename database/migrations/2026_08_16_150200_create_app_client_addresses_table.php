<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla de detalle: sin `code`, se edita desde la pantalla del cliente.
     * Un cliente puede tener varias direcciones de entrega (sucursales).
     */
    public function up(): void
    {
        Schema::create('app_client_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('client_id');

            $table->enum('type', ['billing', 'shipping'])->default('shipping');

            /** Alias de la dirección ("Sucursal Centro"). */
            $table->string('name', 150);
            $table->string('address', 500);
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();

            /**
             * Ruta de esta dirección. La foreign key contra app_routes se agrega
             * junto con el módulo de Rutas (Logística), que aún no existe.
             */
            $table->uuid('route_id')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            /** Dirección sugerida al crear documentos: como máximo una por cliente. */
            $table->enum('is_default', ['yes', 'no'])->default('no');

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('client_id')
                ->references('id')
                ->on('app_clients')
                ->cascadeOnDelete();

            $table->index('client_id');
            $table->index('company_id');
            $table->index('route_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_client_addresses', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['client_id']);
        });

        Schema::dropIfExists('app_client_addresses');
    }
};
