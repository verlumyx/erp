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
        Schema::create('app_account_renewals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('account_id');
            $table->string('type', 20)->default('renewal');
            $table->decimal('amount', 10, 2);
            $table->date('period_start');
            $table->date('period_end');
            $table->date('paid_at');
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestampsTz();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->cascadeOnDelete();

            // ON DELETE CASCADE: las renovaciones son líneas anidadas de la cuenta.
            $table->foreign('account_id')
                ->references('id')
                ->on('app_accounts')
                ->cascadeOnDelete();

            // Auditoría: si el usuario se elimina, el histórico se conserva.
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('company_id');
            $table->index('account_id');
            $table->index('period_end');
            $table->index(['account_id', 'created_at']);
        });

        // CHECKs: SQLite no soporta ADD CONSTRAINT vía ALTER,
        // así que solo se aplican en motores que lo permiten (PostgreSQL).
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_account_renewals ADD CONSTRAINT app_account_renewals_amount_check CHECK (amount >= 0)');
            DB::statement("ALTER TABLE app_account_renewals ADD CONSTRAINT app_account_renewals_type_check CHECK (type IN ('purchase','renewal'))");
            DB::statement('ALTER TABLE app_account_renewals ADD CONSTRAINT app_account_renewals_period_check CHECK (period_end >= period_start)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_account_renewals');
    }
};
