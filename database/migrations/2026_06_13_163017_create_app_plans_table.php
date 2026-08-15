<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('code', 12);
            $table->uuid('service_id');
            $table->string('name', 150);
            $table->enum('capacity', ['profile', 'full_account']);
            $table->integer('duration_days');
            $table->decimal('sale_price', 10, 2);
            $table->decimal('roi_target_pct', 5, 2);
            $table->boolean('active')->default(true);
            $table->timestampsTz();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->cascadeOnDelete();

            // ON DELETE RESTRICT: un servicio con planes asociados no puede eliminarse.
            $table->foreign('service_id')
                ->references('id')
                ->on('app_services')
                ->restrictOnDelete();

            $table->index('company_id');
            $table->index('service_id');
            $table->index('name');
            $table->index('active');
            $table->index('created_at');

            // Único por compañía (cada empresa tiene su propio catálogo y secuencia).
            $table->unique(['company_id', 'code']);
        });

        // CHECKs numéricos: SQLite no soporta ADD CONSTRAINT vía ALTER,
        // así que solo se aplican en motores que lo permiten (PostgreSQL).
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_plans ADD CONSTRAINT app_plans_duration_days_check CHECK (duration_days >= 1)');
            DB::statement('ALTER TABLE app_plans ADD CONSTRAINT app_plans_sale_price_check CHECK (sale_price >= 0)');
            DB::statement('ALTER TABLE app_plans ADD CONSTRAINT app_plans_roi_target_pct_check CHECK (roi_target_pct >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_plans');
    }
};
