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
        Schema::create('app_role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('role_id');
            $table->string('permission', 100);
            $table->timestamps();

            // Foreign key
            $table->foreign('role_id')
                ->references('id')
                ->on('app_roles')
                ->onDelete('cascade');

            // Índices
            $table->index('role_id');
            $table->index('permission');
            $table->index('created_at');

            // Índice único para evitar permisos duplicados por rol
            $table->unique(['role_id', 'permission']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_role_permissions');
    }
};
