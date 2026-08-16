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
        Schema::create('app_warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();
            $table->string('name', 150);
            $table->enum('type', ['main', 'branch', 'transit', 'quarantine', 'virtual'])->default('main');
            $table->string('address', 500)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('city', 100)->nullable();
            $table->uuid('responsible_user_id')->nullable();
            $table->enum('is_default', ['yes', 'no'])->default('no');
            $table->enum('allows_negative_stock', ['yes', 'no'])->default('no');
            $table->enum('uses_locations', ['yes', 'no'])->default('no');
            $table->enum('is_sales_available', ['yes', 'no'])->default('yes');
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            /** Foreign key: encargado de la bodega */
            $table->foreign('responsible_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /** Foreign key: quién registró la bodega (trazabilidad) */
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices
            $table->index('company_id');
            $table->index('name');
            $table->index('type');
            $table->index('is_default');
            $table->index('responsible_user_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');

            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_warehouses', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['responsible_user_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_warehouses');
    }
};
