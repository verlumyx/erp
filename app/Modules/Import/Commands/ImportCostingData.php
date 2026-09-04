<?php

declare(strict_types=1);

namespace App\Modules\Import\Commands;

/**
 * El reparto ya resuelto: lo que el repositorio escribe sin volver a calcular
 * nada.
 *
 * Nada de esto viaja desde la pantalla. Los cargos llegan en su moneda y aquí
 * salen convertidos a la del expediente; los ítems ni siquiera se capturan: se
 * derivan de las recepciones, con su cantidad aceptada, su costo de entrada y
 * la parte del gasto que les tocó.
 */
class ImportCostingData
{
    /**
     * @param  array<int, array{exchange_rate: float, converted_amount: float}>  $charges
     *                                                                           Indexados igual que los costos del comando.
     * @param  array<int, array{
     *     entry_line_id: string,
     *     item_id: string,
     *     measurement_unit_id: string,
     *     location_id: ?string,
     *     base_quantity: float,
     *     remaining_quantity: float,
     *     unit_cost: float,
     *     base_value: float,
     *     allocation_base: float,
     *     allocated_amount: float,
     *     unit_delta: float,
     *     new_unit_cost: float,
     *     capitalized_amount: float,
     *     variance_amount: float,
     *     status: string,
     *     lots: array<int, array{
     *         entry_line_lot_id: string,
     *         lot_id: string,
     *         base_quantity: float,
     *         remaining_quantity: float,
     *         allocation_base: float,
     *         allocated_amount: float,
     *         unit_delta: float,
     *         new_unit_cost: float,
     *         capitalized_amount: float,
     *         variance_amount: float,
     *         status: string
     *     }>
     * }>  $lines
     */
    public function __construct(
        public readonly array $charges = [],
        public readonly array $lines = [],
        public readonly float $totalCharges = 0.0,
        public readonly float $totalBaseValue = 0.0,
        public readonly float $totalLandedValue = 0.0,
        public readonly float $capitalizedAmount = 0.0,
        public readonly float $varianceAmount = 0.0,
    ) {}

    /**
     * Los totales de la cabecera, tal como se persisten.
     *
     * @return array<string, float>
     */
    public function toTotals(): array
    {
        return [
            'total_charges' => $this->totalCharges,
            'total_base_value' => $this->totalBaseValue,
            'total_landed_value' => $this->totalLandedValue,
            'capitalized_amount' => $this->capitalizedAmount,
            'variance_amount' => $this->varianceAmount,
        ];
    }

    /** ¿Hay algo que revalorizar? Sin gasto capitalizable no hay ajuste que generar. */
    public function hasCapitalizableAmount(): bool
    {
        foreach ($this->lines as $line) {
            if ($line['status'] === 'active' && abs($line['capitalized_amount']) > 0.0) {
                return true;
            }
        }

        return false;
    }
}
