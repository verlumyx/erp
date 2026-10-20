<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Movimientos de efectivo en caja: entradas, sangrías o gastos menores.
     */
    public function up(): void
    {
        Schema::create('app_pos_cash_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('pos_session_id');
            $table->enum('movement_type', ['in', 'out']);
            $table->enum('concept', ['change_replenishment', 'cash_drop', 'expense', 'other']);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('reason', 255);
            $table->uuid('authorized_by')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('pos_session_id')
                ->references('id')
                ->on('app_pos_sessions')
                ->cascadeOnDelete();

            $table->foreign('authorized_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices base
            $table->index('company_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');

            // Índices propios
            $table->index('pos_session_id');
            $table->index('movement_type');
        });
    }

    public function down(): void
    {
        Schema::table('app_pos_cash_movements', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['pos_session_id']);
            $table->dropForeign(['authorized_by']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_pos_cash_movements');
    }
};
