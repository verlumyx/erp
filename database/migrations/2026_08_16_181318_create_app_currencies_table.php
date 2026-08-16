<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo global de monedas.
     *
     * A diferencia del resto de catálogos, NO es por empresa: no lleva
     * `company_id` ni `code`. Es la única fuente de los selects de moneda de
     * todo el sistema (precios, proveedores, tasas y documentos).
     */
    public function up(): void
    {
        Schema::create('app_currencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 3)->unique();
            $table->string('name', 60);
            $table->string('symbol', 5);
            $table->integer('order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('status');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_currencies');
    }
};
