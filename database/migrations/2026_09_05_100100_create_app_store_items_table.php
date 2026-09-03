<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Publicación: «este artículo se muestra en la tienda así». `app_items`
     * no cambia; aquí vive solo lo que el ERP no tiene (textos comerciales,
     * orden, fotos). Es un maestro: se desactiva, no se anula.
     */
    public function up(): void
    {
        Schema::create('app_store_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->uuid('item_id');

            /** Identificador en la URL de la tienda: `/productos/{slug}`. */
            $table->string('slug', 160);

            $table->string('title', 150);
            $table->string('summary', 300)->nullable();
            $table->text('description')->nullable();

            $table->enum('is_featured', ['yes', 'no'])->default('no');
            $table->integer('order')->default(0);

            /** Fecha de la primera publicación: se llena al pasar a `active` la primera vez. */
            $table->timestamp('published_at')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('item_id')
                ->references('id')
                ->on('app_items')
                ->restrictOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices base
            $table->index('company_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');
            $table->unique(['company_id', 'code']);

            // Índices propios del módulo
            $table->unique(['company_id', 'item_id']);
            $table->unique(['company_id', 'slug']);
            $table->index('item_id');
            $table->index('is_featured');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::table('app_store_items', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_store_items');
    }
};
