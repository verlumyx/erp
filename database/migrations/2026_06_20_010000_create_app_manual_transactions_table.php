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
        Schema::create('app_manual_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('code', 12);
            $table->date('date');
            $table->string('payment_method', 30);
            $table->string('reference', 100)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('recorded_by')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->cascadeOnDelete();

            // El histórico se preserva aunque se elimine el usuario que registró.
            $table->foreign('recorded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Único por compañía (cada empresa tiene su propia secuencia de código).
            $table->unique(['company_id', 'code']);

            $table->index('company_id');
            $table->index('date', 'idx_manual_transactions_date');
        });

        // CHECKs: SQLite no soporta ADD CONSTRAINT vía ALTER,
        // así que solo se aplican en motores que lo permiten (PostgreSQL).
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_manual_transactions ADD CONSTRAINT app_manual_transactions_total_check CHECK (total >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_manual_transactions');
    }
};
