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
        Schema::create('app_leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('phone', 30);
            $table->string('status', 20)->default('pending');
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('status');
            $table->index('created_at');
        });

        // CHECK: SQLite no soporta ADD CONSTRAINT vía ALTER,
        // así que solo se aplica en motores que lo permiten (PostgreSQL).
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE app_leads ADD CONSTRAINT app_leads_status_check CHECK (status IN ('pending','reviewed'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_leads');
    }
};
