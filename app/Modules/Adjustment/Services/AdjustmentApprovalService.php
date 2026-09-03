<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Services;

use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Adjustment\Models\AdjustmentLine;
use App\Modules\Adjustment\Models\AdjustmentLineLot;
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

        $checks = $this->checksOf($lines);

        $stock = $this->stock->resolveForKeys(
            $adjustment->company_id,
            $adjustment->warehouse_id,
            array_column($checks, 'key'),
        );

        $errors = [];

        foreach ($checks as $index => $check) {
            $current = round($stock[$index]['system'] ?? 0.0, 4);

            if ($current !== $check['captured']) {
                $errors[$check['field']] = "La existencia de {$check['label']} cambió de {$check['captured']} a {$current} desde el conteo: hay que recontarla.";
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
     * Cada existencia que el ajuste dice haber contado, con el número que
     * capturó. Una línea con lotes no se comprueba entera: lo que se contó son
     * sus lotes, y es el saldo de cada uno el que pudo moverse.
     *
     * @param  array<int, AdjustmentLine>  $lines
     * @return array<int, array{key: array{item_id: string, measurement_unit_id: string, location_id: ?string, lot_id: ?string}, captured: float, field: string, label: string}>
     */
    private function checksOf(array $lines): array
    {
        $checks = [];

        foreach ($lines as $index => $line) {
            $lots = $line->lots->where('status', 'active');

            if ($lots->isEmpty()) {
                $checks[] = [
                    'key' => [
                        'item_id' => $line->item_id,
                        'measurement_unit_id' => $line->measurement_unit_id,
                        'location_id' => $line->location_id,
                        'lot_id' => null,
                    ],
                    'captured' => round((float) $line->system_quantity, 4),
                    'field' => "lines.{$index}.system_quantity",
                    'label' => "la línea {$line->line_number}",
                ];

                continue;
            }

            foreach ($lots as $lot) {
                /** @var AdjustmentLineLot $lot */
                $checks[] = [
                    'key' => [
                        'item_id' => $line->item_id,
                        'measurement_unit_id' => $line->measurement_unit_id,
                        'location_id' => $line->location_id,
                        'lot_id' => $lot->lot_id,
                    ],
                    'captured' => round((float) $lot->system_quantity, 4),
                    'field' => "lines.{$index}.lots",
                    'label' => "el lote {$lot->line_number} de la línea {$line->line_number}",
                ];
            }
        }

        return $checks;
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
