<?php

declare(strict_types=1);

namespace App\Modules\Store\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\Tax\Models\Tax;
use Database\Factories\StoreOrderLineFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreOrderLine extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_store_order_lines';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'store_order_id',
        'line_number',
        'item_id',
        'store_item_id',
        'measurement_unit_id',
        'quantity',
        'base_quantity',
        'unit_price',
        'list_price',
        'discount_percent',
        'discount_amount',
        'tax_id',
        'tax_percent',
        'tax_amount',
        'withholding_percent',
        'withholding_amount',
        'subtotal',
        'total',
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
            'list_price' => 'decimal:6',
            'discount_percent' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'tax_percent' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'withholding_percent' => 'decimal:4',
            'withholding_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function storeOrder(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function storeItem(): BelongsTo
    {
        return $this->belongsTo(StoreItem::class, 'store_item_id', 'id');
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'measurement_unit_id', 'id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id', 'id');
    }

    protected static function newFactory(): StoreOrderLineFactory
    {
        return StoreOrderLineFactory::new();
    }
}
