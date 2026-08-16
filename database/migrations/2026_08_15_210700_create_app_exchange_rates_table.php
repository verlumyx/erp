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
        Schema::create('app_exchange_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();
            $table->string('currency', 3);
            $table->date('rate_date');
            $table->decimal('rate', 18, 8)->default(0);
            $table->enum('type', ['legal', 'manual'])->default('legal');
            $table->string('source', 150)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            // Empresa dueña del registro
            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            // Foreign key: quién registró la tasa (trazabilidad)
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices base
            $table->index('company_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');
            $table->index('currency');
            $table->index('rate_date');
            $table->index('type');

            // Únicos por empresa
            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'currency', 'rate_date', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_exchange_rates', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_exchange_rates');
    }
};
