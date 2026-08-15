<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Account\Models\Profile;
use App\Modules\Sale\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SalesExpireCommand extends Command
{
    protected $signature = 'sales:expire';

    protected $description = 'Marca como expiradas las ventas vencidas y libera los profiles cuyo periodo de gracia terminó.';

    public function handle(): int
    {
        $expired = $this->expireDueSales();
        $released = $this->releaseGraceExpiredSales();

        $this->info("Ventas expiradas: {$expired}. Ventas con profiles liberados: {$released}.");

        return self::SUCCESS;
    }

    /**
     * Paso 1: marca como 'expired' las ventas activas cuyo vencimiento ya pasó.
     * No libera profiles todavía (periodo de gracia). Una transacción por venta.
     */
    private function expireDueSales(): int
    {
        $today = now()->toDateString();
        $count = 0;

        Sale::query()
            ->where('status', Sale::STATUS_ACTIVE)
            ->whereDate('end_date', '<', $today)
            ->each(function (Sale $sale) use (&$count): void {
                DB::transaction(function () use ($sale): void {
                    $sale->update(['status' => Sale::STATUS_EXPIRED]);
                });

                $count++;
            });

        return $count;
    }

    /**
     * Paso 2: para ventas expiradas fuera del periodo de gracia, libera sus
     * profiles ocupados (vuelven a 'available'). Una transacción por venta.
     */
    private function releaseGraceExpiredSales(): int
    {
        $graceDays = (int) config('sales.grace_period_days');
        $cutoff = now()->subDays($graceDays)->toDateString();
        $count = 0;

        Sale::query()
            ->where('status', Sale::STATUS_EXPIRED)
            ->whereDate('end_date', '<', $cutoff)
            ->with('saleProfiles:id,sale_id,profile_id')
            ->each(function (Sale $sale) use (&$count): void {
                $profileIds = $sale->saleProfiles->pluck('profile_id')->all();

                if ($profileIds === []) {
                    return;
                }

                $affected = DB::transaction(function () use ($profileIds): int {
                    return Profile::query()
                        ->whereIn('id', $profileIds)
                        ->where('status', 'occupied')
                        ->update(['status' => 'available']);
                });

                if ($affected > 0) {
                    $count++;
                }
            });

        return $count;
    }
}
