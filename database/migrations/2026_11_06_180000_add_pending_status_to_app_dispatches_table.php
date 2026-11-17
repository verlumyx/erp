<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_dispatches DROP CONSTRAINT IF EXISTS app_dispatches_status_check');
            DB::statement("ALTER TABLE app_dispatches ADD CONSTRAINT app_dispatches_status_check CHECK (status IN ('draft', 'pending', 'confirmed', 'delivered', 'cancelled'))");
        } else {
            Schema::table('app_dispatches', function (Blueprint $table): void {
                $table->enum('status', ['draft', 'pending', 'confirmed', 'delivered', 'cancelled'])->default('draft')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_dispatches DROP CONSTRAINT IF EXISTS app_dispatches_status_check');
            DB::statement("ALTER TABLE app_dispatches ADD CONSTRAINT app_dispatches_status_check CHECK (status IN ('draft', 'confirmed', 'delivered', 'cancelled'))");
        } else {
            Schema::table('app_dispatches', function (Blueprint $table): void {
                $table->enum('status', ['draft', 'confirmed', 'delivered', 'cancelled'])->default('draft')->change();
            });
        }
    }
};
