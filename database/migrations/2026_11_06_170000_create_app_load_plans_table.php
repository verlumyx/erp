<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabecera del Plan de Carga: agrupa múltiples despachos para un mismo viaje
     * asignados a una ruta/flete y a un camión de la flota.
     */
    public function up(): void
    {
        Schema::create('app_load_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->date('plan_date');
            $table->uuid('route_id');
            $table->uuid('warehouse_id');
            $table->uuid('vehicle_id');

            $table->string('driver_name', 150);
            $table->uuid('driver_id')->nullable();
            $table->string('helper_name', 255)->nullable();

            $table->text('description')->nullable();

            $table->integer('total_dispatches')->default(0);
            $table->decimal('total_weight', 18, 4)->default(0);
            $table->decimal('total_volume', 18, 4)->default(0);

            $table->enum('status', ['draft', 'in_transit', 'confirmed', 'cancelled'])
                ->default('draft');

            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('route_id')
                ->references('id')
                ->on('app_routes')
                ->restrictOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->restrictOnDelete();

            $table->foreign('vehicle_id')
                ->references('id')
                ->on('app_vehicles')
                ->restrictOnDelete();

            $table->foreign('driver_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(['company_id', 'code'], 'app_load_plans_company_code_unique');
            $table->index('company_id');
            $table->index('route_id');
            $table->index('warehouse_id');
            $table->index('vehicle_id');
            $table->index('plan_date');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_load_plans');
    }
};
