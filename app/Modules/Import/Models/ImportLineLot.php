<?php

declare(strict_types=1);

namespace App\Modules\Import\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Entry\Models\EntryLineLot;
use App\Modules\ItemLot\Models\ItemLot;
use Database\Factories\ImportLineLotFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * El reparto de una de las cajas con las que llegó la línea.
 *
 * `remaining_quantity` por lote es exacta: el lote sí se rastrea unidad a
 * unidad, así que se sabe cuánto de esa caja sigue en la bodega. Por eso el
 * reparto es por lote y no por línea prorrateada después.
 */
class ImportLineLot extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_import_line_lots';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'import_line_id',
        'line_number',
        'entry_line_lot_id',
        'lot_id',
        'base_quantity',
        'remaining_quantity',
        'allocation_base',
        'allocated_amount',
        'unit_delta',
        'new_unit_cost',
        'capitalized_amount',
        'variance_amount',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'base_quantity' => 'decimal:4',
            'remaining_quantity' => 'decimal:4',
            'allocation_base' => 'decimal:4',
            'allocated_amount' => 'decimal:2',
            'unit_delta' => 'decimal:6',
            'new_unit_cost' => 'decimal:6',
            'capitalized_amount' => 'decimal:2',
            'variance_amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function importLine(): BelongsTo
    {
        return $this->belongsTo(ImportLine::class, 'import_line_id', 'id');
    }

    /** La fila de lote de la entrada de la que sale todo. */
    public function entryLineLot(): BelongsTo
    {
        return $this->belongsTo(EntryLineLot::class, 'entry_line_lot_id', 'id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class, 'lot_id', 'id');
    }

    protected static function newFactory(): ImportLineLotFactory
    {
        return ImportLineLotFactory::new();
    }
}
