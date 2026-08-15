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
        Schema::create('app_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('code', 12);
            $table->uuid('service_id');
            $table->string('email', 255);
            $table->text('password_encrypted');
            $table->decimal('cost', 10, 2);
            $table->date('purchase_date');
            $table->date('next_renewal');
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->cascadeOnDelete();

            // ON DELETE RESTRICT: un servicio con cuentas asociadas no puede eliminarse.
            $table->foreign('service_id')
                ->references('id')
                ->on('app_services')
                ->restrictOnDelete();

            $table->index('company_id');
            $table->index('service_id');
            $table->index('status');
            $table->index('created_at');

            // Único por compañía (cada empresa tiene su propia secuencia de código).
            $table->unique(['company_id', 'code']);

            // Una misma cuenta de servicio (email) no puede repetirse dentro del mismo servicio.
            $table->unique(['service_id', 'email']);
        });

        // CHECKs: SQLite no soporta ADD CONSTRAINT vía ALTER,
        // así que solo se aplican en motores que lo permiten (PostgreSQL).
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_accounts ADD CONSTRAINT app_accounts_cost_check CHECK (cost >= 0)');
            DB::statement("ALTER TABLE app_accounts ADD CONSTRAINT app_accounts_status_check CHECK (status IN ('active','down','maintenance','cancelled'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_accounts');
    }
};
