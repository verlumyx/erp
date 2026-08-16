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
        Schema::create('app_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->string('sku', 60);
            $table->string('barcode', 60)->nullable();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->enum('type', ['inventoried', 'non_inventoried', 'service', 'kit', 'serialized'])
                ->default('inventoried');

            $table->uuid('category_id')->nullable();

            /**
             * Impuestos por defecto en compra y venta. La foreign key contra
             * app_taxes se agrega junto con el módulo de Impuestos, que aún no existe.
             */
            $table->uuid('sale_tax_id')->nullable();
            $table->uuid('purchase_tax_id')->nullable();

            $table->enum('cost_method', ['average', 'fifo', 'standard'])->default('average');
            $table->decimal('standard_cost', 18, 6)->default(0);

            /** Solo lectura para el usuario: lo recalcula el proceso de entrada. */
            $table->decimal('average_cost', 18, 6)->default(0);

            $table->decimal('min_price', 18, 6)->default(0);
            $table->enum('is_purchasable', ['yes', 'no'])->default('yes');
            $table->enum('is_sellable', ['yes', 'no'])->default('yes');
            $table->decimal('min_stock', 18, 4)->default(0);
            $table->decimal('max_stock', 18, 4)->default(0);
            $table->decimal('reorder_quantity', 18, 4)->default(0);
            $table->decimal('weight', 18, 4)->default(0);
            $table->decimal('volume', 18, 4)->default(0);
            $table->string('image_path', 500)->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('category_id')
                ->references('id')
                ->on('app_categories')
                ->nullOnDelete();

            /** Foreign key: quién registró el artículo (trazabilidad) */
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices base
            $table->index('company_id');
            $table->index('name');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');

            // Índices propios del módulo
            $table->index('category_id');
            $table->index('type');
            $table->index('is_sellable');
            $table->index('is_purchasable');

            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'sku']);
            $table->unique(['company_id', 'barcode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_items', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_items');
    }
};
