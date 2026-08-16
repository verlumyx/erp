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
        Schema::create('app_taxes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('percentage', 7, 4)->default(0);

            /** Si es 'yes', además del impuesto se practica retención. */
            $table->enum('has_withholding', ['yes', 'no'])->default('no');
            $table->decimal('withholding_percentage', 7, 4)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            // Empresa dueña del registro
            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            // Foreign key: quién registró el impuesto (trazabilidad)
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices base
            $table->index('company_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');
            $table->index('name');
            $table->index('has_withholding');

            // Únicos por empresa
            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_taxes', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_taxes');
    }
};
