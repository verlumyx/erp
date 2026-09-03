<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Models;

use App\Modules\Company\Models\Company;
use App\Modules\ItemLot\Models\ItemLot;
use Database\Factories\AdjustmentLineLotFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uno de los lotes que se contaron en una línea del ajuste.
 *
 * Es una mini-línea: lo contado se compara contra la existencia de ese lote, y
 * de esa resta salen la diferencia, la dirección y el costo con los que el
 * kardex escribe **su** movimiento. Por eso el lote nunca se estrena aquí: el
 * ajuste corrige existencias que ya existen.
 */
class AdjustmentLineLot extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_adjustment_line_lots';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'adjustment_line_id',
        'line_number',
        'lot_id',
        'counted_quantity',
        'system_quantity',
        'difference_quantity',
        'base_quantity',
        'movement_type',
        'unit_cost',
        'total_cost',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'counted_quantity' => 'decimal:4',
            'system_quantity' => 'decimal:4',
            'difference_quantity' => 'decimal:4',
            'base_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:6',
            'total_cost' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function adjustmentLine(): BelongsTo
    {
        return $this->belongsTo(AdjustmentLine::class, 'adjustment_line_id', 'id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class, 'lot_id', 'id');
    }

    /** Las unidades de este lote que el conteo nombra por su serie. */
    public function serials(): HasMany
    {
        return $this->hasMany(AdjustmentLineSerial::class, 'adjustment_line_lot_id', 'id');
    }

    /**
     * Cantidad que el kardex mueve por este lote: su diferencia en unidad base,
     * siempre positiva, porque la dirección la pone `movement_type`.
     */
    public function movedQuantity(): float
    {
        return round(abs((float) $this->base_quantity), 4);
    }

    protected static function newFactory(): AdjustmentLineLotFactory
    {
        return AdjustmentLineLotFactory::new();
    }
}
