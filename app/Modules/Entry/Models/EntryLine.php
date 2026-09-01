<?php

declare(strict_types=1);

namespace App\Modules\Entry\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Database\Factories\EntryLineFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EntryLine extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_entry_lines';

    public $incrementing = false;

    protected $keyType = 'string';

    /** Líneas de documento que hoy pueden originar una línea de entrada. */
    public const SOURCE_TYPES = [PurchaseOrderLine::MORPH_ALIAS];

    protected $fillable = [
        'id',
        'company_id',
        'entry_id',
        'line_number',
        'item_id',
        'measurement_unit_id',
        'sourceable_type',
        'sourceable_id',
        'location_id',
        'lot_number',
        'lot_id',
        'expires_at',
        'serial_numbers',
        'quantity',
        'base_quantity',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_id',
        'tax_percent',
        'tax_amount',
        'withholding_percent',
        'withholding_amount',
        'subtotal',
        'total',
        'received_quantity',
        'rejected_quantity',
        'unit_cost',
        'landed_cost',
        'rejection_reason',
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
            'expires_at' => 'date',
            'serial_numbers' => 'array',
            'quantity' => 'decimal:4',
            'base_quantity' => 'decimal:4',
            'unit_price' => 'decimal:6',
            'discount_percent' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'tax_percent' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'withholding_percent' => 'decimal:4',
            'withholding_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'received_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:6',
            'landed_cost' => 'decimal:6',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'measurement_unit_id', 'id');
    }

    /** Línea del documento origen que esta línea recibe. */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id', 'id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class, 'lot_id', 'id');
    }

    /**
     * Cantidad aceptada llevada a la unidad base, que es la única en la que el
     * kardex mueve saldo. Se deriva del factor que la línea ya congeló en
     * `base_quantity`, sin volver a consultar las unidades del artículo.
     */
    public function baseReceivedQuantity(): float
    {
        $quantity = (float) $this->quantity;

        if ($quantity <= 0.0) {
            return 0.0;
        }

        return round((float) $this->base_quantity * (float) $this->received_quantity / $quantity, 4);
    }

    protected static function newFactory(): EntryLineFactory
    {
        return EntryLineFactory::new();
    }
}
