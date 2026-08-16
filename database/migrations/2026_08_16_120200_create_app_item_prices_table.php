<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla de detalle: sin `code`, se edita desde la pantalla del artículo.
     */
    public function up(): void
    {
        Schema::create('app_item_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('item_id');
            $table->uuid('price_list_id');

            /** Precio unitario en la unidad base del artículo. */
            $table->decimal('price', 18, 6)->default(0);
            $table->string('currency', 3);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('item_id')
                ->references('id')
                ->on('app_items')
                ->cascadeOnDelete();

            $table->foreign('price_list_id')
                ->references('id')
                ->on('app_price_lists')
                ->restrictOnDelete();

            $table->index('company_id');
            $table->index('price_list_id');
            $table->index('status');

            $table->unique(['item_id', 'price_list_id', 'valid_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_item_prices', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['price_list_id']);
        });

        Schema::dropIfExists('app_item_prices');
    }
};
