<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de vehículos / camiones de reparto en Logística.
     * Centraliza las placas, marcas, modelos y capacidades físicas (peso y volumen)
     * para el control de flota y planificación en los Planes de Carga.
     */
    public function up(): void
    {
        Schema::create('app_vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->string('plate', 20);
            $table->string('name', 150);
            $table->string('brand', 80)->nullable();
            $table->string('model', 80)->nullable();
            $table->integer('year')->nullable();

            $table->enum('vehicle_type', ['pickup', 'cava_350', 'truck', 'heavy_truck', 'other'])
                ->default('truck');

            $table->decimal('capacity_weight', 18, 4)->default(0);
            $table->decimal('capacity_volume', 18, 4)->default(0);

            $table->text('notes')->nullable();

            $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(['company_id', 'plate'], 'app_vehicles_company_plate_unique');
            $table->index('company_id');
            $table->index('status');
            $table->index('plate');
            $table->index('created_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_vehicles');
    }
};
