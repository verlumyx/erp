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
        Schema::create('app_item_lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();
            $table->uuid('item_id');
            $table->string('lot_number', 60);
            $table->date('manufactured_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->uuid('supplier_id')->nullable();
            $table->enum('status', ['active', 'blocked', 'expired'])->default('active');
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

            /** Proveedor que suministró el lote; opcional para lotes internos. */
            $table->foreign('supplier_id')
                ->references('id')
                ->on('app_suppliers')
                ->nullOnDelete();

            /** Foreign key: quién registró el lote (trazabilidad) */
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices
            $table->index('company_id');
            $table->index('item_id');
            $table->index('supplier_id');
            $table->index('expires_at');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');

            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'item_id', 'lot_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_item_lots', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_item_lots');
    }
};
