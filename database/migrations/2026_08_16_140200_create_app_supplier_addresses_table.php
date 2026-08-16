<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla de detalle: sin `code`, se edita desde la pantalla del proveedor.
     */
    public function up(): void
    {
        Schema::create('app_supplier_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('supplier_id');

            $table->enum('type', ['billing', 'pickup', 'warehouse'])->default('billing');
            $table->string('address', 500);
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();

            /** Dirección sugerida: una por tipo. */
            $table->enum('is_default', ['yes', 'no'])->default('no');

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('supplier_id')
                ->references('id')
                ->on('app_suppliers')
                ->cascadeOnDelete();

            $table->index('supplier_id');
            $table->index('company_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_supplier_addresses', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['supplier_id']);
        });

        Schema::dropIfExists('app_supplier_addresses');
    }
};
