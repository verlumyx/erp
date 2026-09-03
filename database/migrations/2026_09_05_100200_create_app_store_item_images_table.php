<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Galería de la publicación. Tabla de detalle: quitar una foto es
     * desactivarla. La de `order` menor es la portada.
     */
    public function up(): void
    {
        Schema::create('app_store_item_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('store_item_id');

            /** Ruta en el disco `public`: `store/{company_id}/{store_item_id}/{uuid}.webp`. */
            $table->string('path', 255);
            $table->string('alt_text', 150)->nullable();
            $table->integer('order')->default(0);

            /** Píxeles; se llenan al guardar. */
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('store_item_id')
                ->references('id')
                ->on('app_store_items')
                ->cascadeOnDelete();

            $table->index('company_id');
            $table->index('status');
            $table->index('store_item_id');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::table('app_store_item_images', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['store_item_id']);
        });

        Schema::dropIfExists('app_store_item_images');
    }
};
