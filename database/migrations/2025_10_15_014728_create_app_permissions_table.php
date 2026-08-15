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
        Schema::create('app_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('module_id');
            $table->string('action', 100);
            $table->string('label', 255);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            // Foreign key
            $table->foreign('module_id')
                ->references('id')
                ->on('app_modules')
                ->onDelete('cascade');

            // Índices
            $table->index('module_id');
            $table->index('action');
            $table->index('is_active');
            $table->index('order');
            $table->index('created_at');

            // Índice único para evitar permisos duplicados por módulo
            $table->unique(['module_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_permissions');
    }
};
