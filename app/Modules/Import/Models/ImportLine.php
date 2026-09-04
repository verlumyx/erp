<?php

declare(strict_types=1);

namespace App\Modules\Import\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Entry\Models\EntryLine;
use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Database\Factories\ImportLineFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uno de los ítems que absorben el gasto.
 *
 * No se captura: se deriva de la línea de entrada que lo trajo, con su cantidad
 * aceptada y su costo de entrada. Una línea que lleva lotes es la suma de los
 * suyos: sus cantidades e importes salen de las filas de lote, no al revés.
 */
class ImportLine extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_import_lines';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'import_id',
        'line_number',
        'entry_line_id',
        'item_id',
        'measurement_unit_id',
        'location_id',
        'base_quantity',
        'remaining_quantity',
        'unit_cost',
        'base_value',
        'allocation_base',
        'allocated_amount',
        'unit_delta',
        'new_unit_cost',
        'capitalized_amount',
        'variance_amount',
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
            'base_quantity' => 'decimal:4',
            'remaining_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:6',
            'base_value' => 'decimal:2',
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

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class, 'import_id', 'id');
    }

    /** La línea de entrada de la que sale todo. */
    public function entryLine(): BelongsTo
    {
        return $this->belongsTo(EntryLine::class, 'entry_line_id', 'id');
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

    /** El reparto por lote, cuando el artículo los lleva. */
    public function lots(): HasMany
    {
        return $this->hasMany(ImportLineLot::class, 'import_line_id', 'id');
    }

    protected static function newFactory(): ImportLineFactory
    {
        return ImportLineFactory::new();
    }
}
