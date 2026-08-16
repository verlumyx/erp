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
        Schema::create('app_supplier_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('supplier_id');

            $table->string('name', 150);
            $table->string('position', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();

            /** Un solo contacto principal por proveedor. */
            $table->enum('is_primary', ['yes', 'no'])->default('no');

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
        Schema::table('app_supplier_contacts', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['supplier_id']);
        });

        Schema::dropIfExists('app_supplier_contacts');
    }
};
