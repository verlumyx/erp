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
        Schema::create('app_sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('code', 12);
            $table->uuid('client_id');
            $table->uuid('plan_id');
            $table->uuid('agent_id');
            // Denormalizado: copia del plan.service_id para filtrar ventas por servicio sin joins.
            $table->uuid('service_id');
            // Snapshot del plan al momento de la venta (la venta vive con sus propios valores).
            $table->string('capacity', 20);
            $table->integer('duration_days');
            $table->decimal('price', 10, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('active');
            $table->timestampTz('cancelled_at')->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->cascadeOnDelete();

            // ON DELETE RESTRICT: no se puede borrar un cliente con ventas asociadas.
            $table->foreign('client_id')
                ->references('id')
                ->on('app_clients')
                ->restrictOnDelete();

            // ON DELETE RESTRICT: no se puede borrar un plan con ventas asociadas.
            $table->foreign('plan_id')
                ->references('id')
                ->on('app_plans')
                ->restrictOnDelete();

            // ON DELETE RESTRICT: preservar la trazabilidad del agente que vendió.
            $table->foreign('agent_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('service_id')
                ->references('id')
                ->on('app_services')
                ->restrictOnDelete();

            // Único por compañía (cada empresa tiene su propia secuencia de código).
            $table->unique(['company_id', 'code']);

            $table->index(['status', 'end_date'], 'idx_sales_status_end_date');
            $table->index('client_id', 'idx_sales_client');
            $table->index('agent_id', 'idx_sales_agent');
            $table->index('service_id', 'idx_sales_service');
        });

        // CHECKs: SQLite no soporta ADD CONSTRAINT vía ALTER,
        // así que solo se aplican en motores que lo permiten (PostgreSQL).
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE app_sales ADD CONSTRAINT app_sales_capacity_check CHECK (capacity IN ('profile','full_account'))");
            DB::statement('ALTER TABLE app_sales ADD CONSTRAINT app_sales_duration_days_check CHECK (duration_days >= 1)');
            DB::statement('ALTER TABLE app_sales ADD CONSTRAINT app_sales_price_check CHECK (price >= 0)');
            DB::statement("ALTER TABLE app_sales ADD CONSTRAINT app_sales_status_check CHECK (status IN ('active','expired','cancelled'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_sales');
    }
};
