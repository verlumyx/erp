<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El precio de un artículo en una lista deja de tener vigencia: hay un solo
     * precio vigente por lista, y el histórico vive en los documentos emitidos.
     *
     * Al desaparecer `valid_from` de la clave, el único pasa a ser
     * (item_id, price_list_id).
     */
    public function up(): void
    {
        Schema::table('app_item_prices', function (Blueprint $table) {
            $table->dropUnique(['item_id', 'price_list_id', 'valid_from']);
            $table->dropColumn(['valid_from', 'valid_to']);
            $table->unique(['item_id', 'price_list_id']);
        });
    }

    public function down(): void
    {
        Schema::table('app_item_prices', function (Blueprint $table) {
            $table->dropUnique(['item_id', 'price_list_id']);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->unique(['item_id', 'price_list_id', 'valid_from']);
        });
    }
};
