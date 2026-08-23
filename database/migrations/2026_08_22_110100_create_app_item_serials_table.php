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
        Schema::create('app_item_serials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();
            $table->uuid('item_id');
            $table->string('serial_number', 100);
            $table->uuid('lot_id')->nullable();
            $table->uuid('warehouse_id')->nullable();
            $table->enum('status', ['available', 'reserved', 'sold', 'returned', 'scrapped'])->default('available');
            $table->timestamp('sold_at')->nullable();
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

            $table->foreign('lot_id')
                ->references('id')
                ->on('app_item_lots')
                ->nullOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->nullOnDelete();

            /** Foreign key: quién registró la serie (trazabilidad) */
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices
            $table->index('company_id');
            $table->index('item_id');
            $table->index('lot_id');
            $table->index('warehouse_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');

            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'item_id', 'serial_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_item_serials', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['lot_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_item_serials');
    }
};
