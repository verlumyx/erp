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
        Schema::create('app_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('code', 12);
            $table->string('name', 100);
            $table->string('logo_url', 255)->nullable();
            $table->integer('max_profiles');
            $table->boolean('active')->default(true);
            $table->timestampsTz();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->cascadeOnDelete();

            $table->index('company_id');
            $table->index('name');
            $table->index('active');
            $table->index('created_at');

            // Únicos por compañía (cada empresa tiene su propio catálogo y secuencia)
            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
        });

        // CHECK (max_profiles >= 1): SQLite no soporta ADD CONSTRAINT vía ALTER,
        // así que solo se aplica en motores que lo permiten (PostgreSQL).
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_services ADD CONSTRAINT app_services_max_profiles_check CHECK (max_profiles >= 1)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_services');
    }
};
