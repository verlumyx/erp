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
        Schema::create('app_refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('code', 12);
            $table->uuid('sale_id');
            // Snapshot del cliente de la venta para listar/filtrar sin joins.
            $table->uuid('client_id');
            $table->decimal('amount', 10, 2);
            $table->string('reason', 255)->nullable();
            $table->string('status', 20)->default('pending');
            $table->uuid('requested_by')->nullable();
            $table->uuid('resolved_by')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->cascadeOnDelete();

            // ON DELETE RESTRICT: preservar la trazabilidad de la venta reembolsada.
            $table->foreign('sale_id')
                ->references('id')
                ->on('app_sales')
                ->restrictOnDelete();

            $table->foreign('client_id')
                ->references('id')
                ->on('app_clients')
                ->restrictOnDelete();

            $table->foreign('requested_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('resolved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Único por compañía (cada empresa tiene su propia secuencia de código).
            $table->unique(['company_id', 'code']);

            $table->index('sale_id', 'idx_refunds_sale');
            $table->index('status', 'idx_refunds_status');
            $table->index('created_at', 'idx_refunds_created_at');
        });

        // CHECKs: SQLite no soporta ADD CONSTRAINT vía ALTER,
        // así que solo se aplican en motores que lo permiten (PostgreSQL).
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_refunds ADD CONSTRAINT app_refunds_amount_check CHECK (amount >= 0)');
            DB::statement("ALTER TABLE app_refunds ADD CONSTRAINT app_refunds_status_check CHECK (status IN ('pending','approved','rejected'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_refunds');
    }
};
