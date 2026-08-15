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
        Schema::table('app_manual_transactions', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('total');
            $table->timestampTz('approved_at')->nullable()->after('status');
            $table->timestampTz('cancelled_at')->nullable()->after('approved_at');

            $table->index(['company_id', 'status'], 'idx_manual_transactions_company_status');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE app_manual_transactions ADD CONSTRAINT app_manual_transactions_status_check CHECK (status IN ('pending','approved','cancelled'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_manual_transactions DROP CONSTRAINT IF EXISTS app_manual_transactions_status_check');
        }

        Schema::table('app_manual_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_manual_transactions_company_status');
            $table->dropColumn(['status', 'approved_at', 'cancelled_at']);
        });
    }
};
