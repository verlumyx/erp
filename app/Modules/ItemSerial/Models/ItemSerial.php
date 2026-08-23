<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\ItemSerialFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemSerial extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_item_serials';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'SER';

    /** Ciclo de vida de una unidad serializada. */
    public const STATUSES = ['available', 'reserved', 'sold', 'returned', 'scrapped'];

    /** Estado que marca la salida definitiva y sella `sold_at`. */
    public const STATUS_SOLD = 'sold';

    /** Solo el artículo serializado se controla unidad por unidad. */
    public const TRACKABLE_ITEM_TYPE = 'serialized';

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'item_id',
        'serial_number',
        'lot_id',
        'warehouse_id',
        'status',
        'sold_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
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

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class, 'lot_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    protected static function newFactory(): ItemSerialFactory
    {
        return ItemSerialFactory::new();
    }
}
