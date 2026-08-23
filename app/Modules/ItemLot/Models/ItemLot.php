<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Item\Models\Item;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\User\Models\User;
use Database\Factories\ItemLotFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemLot extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_item_lots';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'LOT';

    /** Estados propios del lote: no son los `active` / `inactive` de un maestro. */
    public const STATUSES = ['active', 'blocked', 'expired'];

    /** Tipos de artículo que admiten control por lote. */
    public const TRACKABLE_ITEM_TYPES = ['inventoried', 'serialized'];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'item_id',
        'lot_number',
        'manufactured_at',
        'expires_at',
        'supplier_id',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'manufactured_at' => 'date',
            'expires_at' => 'date',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function serials(): HasMany
    {
        return $this->hasMany(ItemSerial::class, 'lot_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    protected static function newFactory(): ItemLotFactory
    {
        return ItemLotFactory::new();
    }
}
