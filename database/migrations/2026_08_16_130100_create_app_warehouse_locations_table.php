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
        Schema::create('app_warehouse_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();
            $table->uuid('warehouse_id');
            $table->uuid('parent_id')->nullable();
            $table->string('name', 100);
            $table->string('location_code', 50);
            $table->enum('type', ['zone', 'aisle', 'shelf', 'bin'])->default('shelf');
            $table->decimal('capacity', 18, 4)->default(0);
            $table->enum('is_default', ['yes', 'no'])->default('no');
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

            /** Foreign key: quién registró la ubicación (trazabilidad) */
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices
            $table->index('company_id');
            $table->index('warehouse_id');
            $table->index('parent_id');
            $table->index('name');
            $table->index('type');
            $table->index('is_default');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');

            $table->unique(['company_id', 'code']);
            $table->unique(['warehouse_id', 'location_code']);
        });

        /**
         * Jerarquía zona → pasillo → estante → posición. La llave foránea a sí
         * misma va aparte: dentro del Schema::create la sentencia de la FK se
         * emite antes del "add primary key", y PostgreSQL la rechaza porque
         * todavía no existe el índice único al que apunta.
         */
        Schema::table('app_warehouse_locations', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('app_warehouse_locations')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_warehouse_locations', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_warehouse_locations');
    }
};
