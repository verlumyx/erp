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
        Schema::create('app_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('status', 55)->default('active');
            $table->text('description')->nullable();
            $table->enum('permission_type', ['all', 'custom'])->default('custom');
            $table->timestamps();

            // Índices
            $table->index('name');
            $table->index('status');
            $table->index('permission_type');
            $table->index('created_at');

            // Índice único para el nombre
            $table->unique('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('app_roles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });

        Schema::dropIfExists('app_roles');
    }
};
