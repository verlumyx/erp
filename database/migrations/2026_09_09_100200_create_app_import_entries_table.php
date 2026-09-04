<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Qué llegó: las recepciones que van a absorber el gasto.
     *
     * El ancla es la entrada y no la factura, aunque el gasto venga de
     * facturas. La factura dice lo que el proveedor cobró; la entrada dice lo
     * que de verdad llegó y es la que escribió el kardex.
     */
    public function up(): void
    {
        Schema::create('app_import_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('import_id');
            $table->uuid('entry_id');

            $table->integer('line_number');

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('import_id')
                ->references('id')
                ->on('app_imports')
                ->cascadeOnDelete();

            $table->foreign('entry_id')
                ->references('id')
                ->on('app_entries')
                ->restrictOnDelete();

            $table->index('entry_id');
            $table->index('company_id');
            $table->index('status');

            $table->unique(['import_id', 'entry_id'], 'app_import_entries_entry_unique');
            $table->unique(['import_id', 'line_number'], 'app_import_entries_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_import_entries', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['import_id']);
            $table->dropForeign(['entry_id']);
        });

        Schema::dropIfExists('app_import_entries');
    }
};
