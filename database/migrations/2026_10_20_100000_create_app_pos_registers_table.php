<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cajas registradoras / Terminales físicas: estaciones de cobro en mostrador.
     */
    public function up(): void
    {
        Schema::create('app_pos_registers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();
            $table->string('name', 100);
            $table->uuid('warehouse_id');
            $table->uuid('price_list_id')->nullable();
            $table->uuid('default_client_id');
            $table->string('receipt_printer_name', 100)->nullable();
            $table->string('invoice_series', 20)->nullable();
            $table->enum('allow_price_override', ['yes', 'no'])->default('no');
            $table->enum('allow_custom_discounts', ['yes', 'no'])->default('no');
            $table->decimal('max_discount_percent', 7, 4)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->restrictOnDelete();

            $table->foreign('price_list_id')
                ->references('id')
                ->on('app_price_lists')
                ->nullOnDelete();

            $table->foreign('default_client_id')
                ->references('id')
                ->on('app_clients')
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

            // Índices propios
            $table->unique(['company_id', 'name']);
            $table->index('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_pos_registers', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['price_list_id']);
            $table->dropForeign(['default_client_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_pos_registers');
    }
};
