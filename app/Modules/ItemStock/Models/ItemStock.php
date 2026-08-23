<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Database\Factories\ItemStockFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saldo actual por artículo / bodega / ubicación / lote.
 *
 * Tabla **derivada**: siempre debe cuadrar contra la suma del kardex. No se
 * captura ni se edita a mano — solo la mueve `ItemStockApplyMovementService`
 * dentro de la transacción del documento que la origina. Por eso no lleva
 * `code`: no es un módulo de captura.
 */
class ItemStock extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_item_stocks';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'item_id',
        'warehouse_id',
        'location_id',
        'lot_id',
        'quantity',
        'reserved_quantity',
        'incoming_quantity',
        'available_quantity',
        'average_cost',
        'total_value',
        'last_movement_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'reserved_quantity' => 'decimal:4',
            'incoming_quantity' => 'decimal:4',
            'available_quantity' => 'decimal:4',
            'average_cost' => 'decimal:6',
            'total_value' => 'decimal:2',
            'last_movement_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id', 'id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class, 'lot_id', 'id');
    }

    protected static function newFactory(): ItemStockFactory
    {
        return ItemStockFactory::new();
    }
}
