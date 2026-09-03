<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menús que una empresa NO ve. Es una lista de exclusión: sin filas, la
     * empresa ve todo lo que su rol permite; cada fila esconde un menú a esa
     * empresa. Así no hay que sembrar nada al crear empresas ni menús nuevos.
     */
    public function up(): void
    {
        Schema::create('app_company_disabled_menus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('menu_id');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('app_companies')->cascadeOnDelete();
            $table->foreign('menu_id')->references('id')->on('app_menus')->cascadeOnDelete();

            $table->unique(['company_id', 'menu_id']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_company_disabled_menus');
    }
};
