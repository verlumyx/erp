<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuración de la empresa: una sola fila por empresa, nunca se lista
     * ni se elimina. Se crea con valores por defecto junto con la empresa.
     */
    public function up(): void
    {
        Schema::create('app_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');

            /**
             * Moneda en la que la empresa lleva sus cifras. Si coincide con la
             * secundaria, el sistema deja de convertir y de mostrar el doble
             * monto en toda la aplicación.
             */
            $table->string('base_currency', 3)->default('USD');

            /** Moneda de presentación obligatoria (el bolívar). Nula la desactiva. */
            $table->string('secondary_currency', 3)->nullable()->default('VES');

            /** Qué tasa valora los documentos por defecto. */
            $table->enum('rate_type', ['legal', 'manual'])->default('legal');

            /** Si el usuario puede pisar a mano la tasa que resuelve el sistema. */
            $table->enum('allows_rate_override', ['yes', 'no'])->default('yes');

            $table->integer('amount_decimals')->default(2);
            $table->integer('price_decimals')->default(6);

            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /** Una sola configuración por empresa. */
            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_configurations', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_configurations');
    }
};
