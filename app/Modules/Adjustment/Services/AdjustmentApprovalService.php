<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Services;

use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Adjustment\Models\AdjustmentLine;
use App\Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use App\Modules\Configuration\Repositories\Contracts\ConfigurationRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Las dos condiciones que hay que cumplir para que un ajuste toque el
 * inventario.
 *
 * La primera es de control interno: por encima del umbral que la empresa
 * configura, el ajuste lo firma alguien distinto de quien lo registró. Un
 * documento que mueve existencia sin una operación comercial detrás es el
 * agujero natural de un inventario, y la única defensa barata es que no lo
 * cierre una sola persona. El umbral se compara contra el valor absoluto del
 * impacto: un faltante grande merece esa segunda firma igual que un sobrante.
 *
 * La segunda es de exactitud: entre el conteo y la aprobación pueden haber
 * pasado horas, y en ese rato la bodega siguió trabajando. Si la existencia
 * cambió, la diferencia que el ajuste iba a aplicar ya no es la que hay, así
 * que el sistema exige recontar en vez de escribir un número viejo.
 */
class AdjustmentApprovalService
{
    public function __construct(
        private readonly AdjustmentRepositoryInterface $repository,
        private readonly ConfigurationRepositoryInterface $configurations,
        private readonly AdjustmentStockService $stock,
    ) {}

    /**
     * @throws ValidationException
     */
    public function guard(Adjustment $adjustment, ?string $approverId): void
    {
        $this->guardApprover($adjustment, $approverId);
        $this->guardStockUnchanged($adjustment);
    }

    /**
     * @throws ValidationException
     */
    private function guardApprover(Adjustment $adjustment, ?string $approverId): void
    {
        $threshold = $this->thresholdOf($adjustment->company_id);

        if (abs((float) $adjustment->net_cost) <= $threshold) {
            return;
        }

        if ($approverId !== null && $approverId !== $adjustment->created_by) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => 'Un ajuste de este impacto lo tiene que aprobar alguien distinto de quien lo registró.',
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function guardStockUnchanged(Adjustment $adjustment): void
    {
        $lines = $this->repository->activeLines($adjustment);

        if ($lines === []) {
            return;
        }

        $stock = $this->stock->resolveForKeys(
            $adjustment->company_id,
            $adjustment->warehouse_id,
            array_map(
                static fn (AdjustmentLine $line): array => [
                    'item_id' => $line->item_id,
                    'measurement_unit_id' => $line->measurement_unit_id,
                    'location_id' => $line->location_id,
                    'lot_id' => $line->lot_id,
                ],
                $lines,
            ),
        );

        $errors = [];

        foreach ($lines as $index => $line) {
            $current = round($stock[$index]['system'] ?? 0.0, 4);
            $captured = round((float) $line->system_quantity, 4);

            if ($current !== $captured) {
                $errors["lines.{$index}.system_quantity"] = "La existencia de la línea {$line->line_number} cambió de {$captured} a {$current} desde el conteo: hay que recontarla.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                ...$errors,
                'status' => 'La existencia cambió desde el conteo: vuelve a guardar el ajuste con los números de ahora.',
            ]);
        }
    }

    /**
     * Umbral configurado por la empresa. Sin configuración, cero: cualquier
     * ajuste con impacto pide la segunda firma, que es el lado seguro.
     */
    private function thresholdOf(?string $companyId): float
    {
        if ($companyId === null) {
            return 0.0;
        }

        $configuration = $this->configurations->findByCompany($companyId);

        return round((float) ($configuration?->adjustment_approval_threshold ?? 0), 2);
    }
}
