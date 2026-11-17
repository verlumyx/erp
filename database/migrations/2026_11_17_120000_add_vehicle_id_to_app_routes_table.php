<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade la relación directa con el catálogo de camiones/vehículos (app_vehicles)
     * a las rutas, permitiendo seleccionar la unidad de la flota.
     */
    public function up(): void
    {
        Schema::table('app_routes', function (Blueprint $table) {
            $table->uuid('vehicle_id')->nullable()->after('salesperson_id');

            $table->foreign('vehicle_id')
                ->references('id')
                ->on('app_vehicles')
                ->nullOnDelete();

            $table->index('vehicle_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_routes', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropColumn('vehicle_id');
        });
    }
};
