<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_sale_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sale_id');
            $table->uuid('profile_id');
            $table->timestampTz('created_at')->nullable();

            // ON DELETE CASCADE: los perfiles ocupados se eliminan con su venta.
            $table->foreign('sale_id')
                ->references('id')
                ->on('app_sales')
                ->cascadeOnDelete();

            // ON DELETE RESTRICT: no se puede borrar un profile asignado a una venta.
            $table->foreign('profile_id')
                ->references('id')
                ->on('app_profiles')
                ->restrictOnDelete();

            // Un profile no puede repetirse dentro de la misma venta.
            $table->unique(['sale_id', 'profile_id']);

            $table->index('profile_id', 'idx_sale_profiles_profile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_sale_profiles');
    }
};
