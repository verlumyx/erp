<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\User\Models\User;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Database\Factories\AdjustmentLineFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdjustmentLine extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_adjustment_lines';

    public $incrementing = false;

    protected $keyType = 'string';

    /** El signo de la diferencia decide en qué dirección se mueve el kardex. */
    public const MOVEMENT_IN = 'adjustment_in';

    public const MOVEMENT_OUT = 'adjustment_out';

    protected $fillable = [
        'id',
        'company_id',
        'adjustment_id',
        'line_number',
        'item_id',
        'measurement_unit_id',
        'location_id',
        'lot_id',
        'serial_id',
        'system_quantity',
        'counted_quantity',
        'difference_quantity',
        'base_quantity',
        'movement_type',
        'unit_cost',
        'total_cost',
        'reason',
        'counted_by',
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
            'system_quantity' => 'decimal:4',
            'counted_quantity' => 'decimal:4',
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

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(Adjustment::class, 'adjustment_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'measurement_unit_id', 'id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id', 'id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class, 'lot_id', 'id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(ItemSerial::class, 'serial_id', 'id');
    }

    /** Quién contó físicamente esta línea. */
    public function counter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by', 'id');
    }

    /**
     * Cantidad que el kardex mueve: la diferencia en unidad base, siempre
     * positiva, porque la dirección la pone `movement_type`.
     */
    public function movedQuantity(): float
    {
        return round(abs((float) $this->base_quantity), 4);
    }

    protected static function newFactory(): AdjustmentLineFactory
    {
        return AdjustmentLineFactory::new();
    }
}
