<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sesiones y Cortes de caja por usuario: turnos individuales de trabajo.
     */
    public function up(): void
    {
        Schema::create('app_pos_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();
            $table->uuid('pos_register_id');
            $table->uuid('user_id');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();

            // Fondos iniciales de apertura
            $table->decimal('opening_cash_usd', 18, 2)->default(0);
            $table->decimal('opening_cash_ves', 18, 2)->default(0);

            // Totales esperados al corte según transacciones
            $table->decimal('expected_cash_usd', 18, 2)->default(0);
            $table->decimal('expected_cash_ves', 18, 2)->default(0);
            $table->decimal('expected_card_ves', 18, 2)->default(0);
            $table->decimal('expected_mobile_ves', 18, 2)->default(0);
            $table->decimal('expected_other', 18, 2)->default(0);

            // Totales declarados en arqueo ciego por el cajero
            $table->decimal('declared_cash_usd', 18, 2)->nullable();
            $table->decimal('declared_cash_ves', 18, 2)->nullable();
            $table->decimal('declared_card_ves', 18, 2)->nullable();
            $table->decimal('declared_mobile_ves', 18, 2)->nullable();
            $table->decimal('declared_other', 18, 2)->nullable();

            // Diferencias calculadas (declarado - esperado)
            $table->decimal('difference_cash_usd', 18, 2)->nullable();
            $table->decimal('difference_cash_ves', 18, 2)->nullable();

            // Métricas de ventas del turno
            $table->integer('sales_count')->default(0);
            $table->integer('cancelled_sales_count')->default(0);
            $table->decimal('total_sales_amount', 18, 2)->default(0);
            $table->decimal('total_discounts_amount', 18, 2)->default(0);

            $table->uuid('supervisor_id')->nullable();
            $table->text('closing_notes')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'completed', 'cancelled'])->default('confirmed');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('pos_register_id')
                ->references('id')
                ->on('app_pos_registers')
                ->restrictOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('supervisor_id')
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
            $table->unique(['company_id', 'code']);

            // Índices propios
            $table->index('pos_register_id');
            $table->index('user_id');
            $table->index('opened_at');
            $table->index('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('app_pos_sessions', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['pos_register_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['supervisor_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_pos_sessions');
    }
};
