<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de detalle: los despachos que viajan en un Plan de Carga concreto.
     */
    public function up(): void
    {
        Schema::create('app_load_plan_dispatches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('load_plan_id');
            $table->uuid('dispatch_id');

            $table->integer('sequence')->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('load_plan_id')
                ->references('id')
                ->on('app_load_plans')
                ->cascadeOnDelete();

            $table->foreign('dispatch_id')
                ->references('id')
                ->on('app_dispatches')
                ->restrictOnDelete();

            $table->unique(
                ['load_plan_id', 'dispatch_id'],
                'app_load_plan_dispatches_plan_dispatch_unique',
            );
            $table->index('company_id');
            $table->index('load_plan_id');
            $table->index('dispatch_id');
            $table->index('status');
            $table->index('sequence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_load_plan_dispatches');
    }
};
