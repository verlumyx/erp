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
        Schema::create('app_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->smallInteger('number');
            $table->string('pin', 10)->nullable();
            $table->string('status', 20)->default('available');
            $table->text('notes')->nullable();
            $table->timestampsTz();

            // ON DELETE CASCADE: los perfiles son líneas anidadas de la cuenta.
            $table->foreign('account_id')
                ->references('id')
                ->on('app_accounts')
                ->cascadeOnDelete();

            $table->index('account_id');
            $table->index('status');

            // No puede repetirse el número de perfil dentro de una misma cuenta.
            $table->unique(['account_id', 'number']);
        });

        // CHECKs: SQLite no soporta ADD CONSTRAINT vía ALTER,
        // así que solo se aplican en motores que lo permiten (PostgreSQL).
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_profiles ADD CONSTRAINT app_profiles_number_check CHECK (number >= 1)');
            DB::statement("ALTER TABLE app_profiles ADD CONSTRAINT app_profiles_status_check CHECK (status IN ('available','occupied','maintenance'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_profiles');
    }
};
