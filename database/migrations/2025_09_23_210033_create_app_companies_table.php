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
        Schema::create('app_companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('address', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('phone', 20)->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            // Índices
            $table->index('name');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');

            // Índice único para el nombre
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_companies');
    }
};
