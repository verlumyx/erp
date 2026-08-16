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
        Schema::create('app_item_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('item_id');
            $table->uuid('measurement_unit_id');

            /** Exactamente una unidad base por artículo; su factor siempre es 1. */
            $table->enum('is_base', ['yes', 'no'])->default('no');
            $table->decimal('conversion_factor', 18, 8)->default(1);

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

            $table->foreign('measurement_unit_id')
                ->references('id')
                ->on('app_measurement_units')
                ->restrictOnDelete();

            $table->index('company_id');
            $table->index('is_base');
            $table->index('status');

            $table->unique(['item_id', 'measurement_unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_item_units', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['measurement_unit_id']);
        });

        Schema::dropIfExists('app_item_units');
    }
};
