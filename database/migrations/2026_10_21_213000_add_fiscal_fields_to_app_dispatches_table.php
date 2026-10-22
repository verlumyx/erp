<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Requerimientos fiscales según Providencia Administrativa SENIAT N° SNAT/2011/00071 (Art. 20 y 21):
     * - Número de control fiscal correlativo e independiente del código de despacho (Art. 13.3).
     * - Indicador de si ampara o no operación de venta (Art. 20).
     * - Motivo del traslado cuando no represente venta (reparación, traslado a depósitos/almacenes, distribución, etc.) (Art. 21).
     * - Detalle o justificación explicativa del traslado.
     */
    public function up(): void
    {
        Schema::table('app_dispatches', function (Blueprint $table): void {
            $table->string('control_number', 30)->nullable()->after('code');
            $table->boolean('is_sale')->default(true)->after('control_number');
            $table->string('transfer_reason', 100)->nullable()->after('is_sale');
            $table->string('transfer_reason_detail', 500)->nullable()->after('transfer_reason');

            $table->index(['company_id', 'control_number']);
        });

        // Backfill de registros existentes
        $dispatches = DB::table('app_dispatches')
            ->orderBy('created_at')
            ->get(['id', 'company_id', 'sourceable_type']);

        $companyCounters = [];
        foreach ($dispatches as $dispatch) {
            $cId = $dispatch->company_id ?? 'global';
            $companyCounters[$cId] = ($companyCounters[$cId] ?? 0) + 1;
            $ctrl = '00-' . str_pad((string) $companyCounters[$cId], 8, '0', STR_PAD_LEFT);

            $isSale = !in_array($dispatch->sourceable_type, ['transfer', 'purchase_return'], true);
            $reason = match ($dispatch->sourceable_type) {
                'transfer' => 'internal_transfer',
                'purchase_return' => 'purchase_return',
                default => 'sale',
            };

            DB::table('app_dispatches')->where('id', $dispatch->id)->update([
                'control_number' => $ctrl,
                'is_sale' => $isSale,
                'transfer_reason' => $reason,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('app_dispatches', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'control_number']);
            $table->dropColumn([
                'control_number',
                'is_sale',
                'transfer_reason',
                'transfer_reason_detail',
            ]);
        });
    }
};
