<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabecera del ajuste: corrección de existencias por conteo físico, merma,
     * daño, vencimiento o error de captura.
     *
     * Es el único documento que mueve inventario sin una operación comercial
     * detrás, y por eso exige motivo y aprobación antes de tocar el kardex.
     */
    public function up(): void
    {
        Schema::create('app_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            /** Bodega ajustada. La línea elige la ubicación dentro de ella. */
            $table->uuid('warehouse_id');

            $table->date('adjustment_date');

            $table->enum('type', [
                'physical_count', 'loss', 'damage', 'expiration',
                'theft', 'correction', 'revaluation', 'other',
            ])->default('physical_count');

            /** Qué se admite en las líneas: solo sobrantes, solo faltantes o ambos. */
            $table->enum('direction', ['in', 'out', 'mixed'])->default('mixed');

            /** Justificación obligatoria: un ajuste sin motivo no se registra. */
            $table->string('reason', 500);

            /** Identificador del conteo físico asociado, si lo hubo. */
            $table->string('count_id', 60)->nullable();

            /** Suma de las líneas activas, en unidad base y separadas por signo. */
            $table->decimal('total_quantity_in', 18, 4)->default(0);
            $table->decimal('total_quantity_out', 18, 4)->default(0);
            $table->decimal('total_cost_in', 18, 2)->default(0);
            $table->decimal('total_cost_out', 18, 2)->default(0);

            /** Impacto en el valor del inventario: entradas menos salidas. */
            $table->decimal('net_cost', 18, 2)->default(0);

            /**
             * Quién autorizó. Se escribe al confirmar, que es el momento en que
             * el ajuste toca la existencia.
             */
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();

            /** Acta de conteo o evidencia que respalda el ajuste. */
            $table->string('attachment_path', 500)->nullable();

            $table->text('notes')->nullable();

            $table->enum('status', [
                'draft', 'pending_approval', 'confirmed', 'completed', 'cancelled',
            ])->default('draft');
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

            /** Foreign key: quién aprobó el ajuste (trazabilidad) */
            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /** Foreign key: quién registró el ajuste (trazabilidad) */
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices base
            $table->index('company_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');

            // Índices propios del módulo
            $table->index('warehouse_id');
            $table->index('adjustment_date');
            $table->index('type');
            $table->index('approved_by');

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('app_adjustments', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_adjustments');
    }
};
