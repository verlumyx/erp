<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Database\Factories\TransferLineFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferLine extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_transfer_lines';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'transfer_id',
        'line_number',
        'item_id',
        'measurement_unit_id',
        'origin_location_id',
        'destination_location_id',
        'lot_id',
        'serial_id',
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
        'sent_quantity',
        'received_quantity',
        'difference_quantity',
        'unit_cost',
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
            'sent_quantity' => 'decimal:4',
            'received_quantity' => 'decimal:4',
            'difference_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:6',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class, 'transfer_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'measurement_unit_id', 'id');
    }

    public function originLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'origin_location_id', 'id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'destination_location_id', 'id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class, 'lot_id', 'id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(ItemSerial::class, 'serial_id', 'id');
    }

    /**
     * Factor con el que la línea se convierte a la unidad base. Lo escribió el
     * repositorio al guardar; aquí se deshace para medir en unidad base lo que
     * la recepción captura en la unidad de la línea.
     */
    public function baseFactor(): float
    {
        $quantity = (float) $this->quantity;

        return $quantity > 0 ? (float) $this->base_quantity / $quantity : 1.0;
    }

    protected static function newFactory(): TransferLineFactory
    {
        return TransferLineFactory::new();
    }
}
