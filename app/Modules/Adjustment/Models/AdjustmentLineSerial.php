<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Models;

use App\Modules\Company\Models\Company;
use App\Modules\ItemSerial\Models\ItemSerial;
use Database\Factories\AdjustmentLineSerialFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una de las unidades con serie que nombra una línea del ajuste.
 *
 * No lleva cantidad: una serie es una unidad. Si la unidad se encontró o si
 * falta lo dice la diferencia de la línea —o la del lote del que sale—, no la
 * fila.
 */
class AdjustmentLineSerial extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_adjustment_line_serials';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'adjustment_line_id',
        'adjustment_line_lot_id',
        'line_number',
        'serial_id',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
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

    public function adjustmentLineLot(): BelongsTo
    {
        return $this->belongsTo(AdjustmentLineLot::class, 'adjustment_line_lot_id', 'id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(ItemSerial::class, 'serial_id', 'id');
    }

    protected static function newFactory(): AdjustmentLineSerialFactory
    {
        return AdjustmentLineSerialFactory::new();
    }
}
