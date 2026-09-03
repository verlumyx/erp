<?php

declare(strict_types=1);

namespace App\Modules\Item\Models;

use App\Modules\Category\Models\Category;
use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_items';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'ART';

    public const TYPES = ['inventoried', 'non_inventoried', 'service', 'kit', 'serialized'];

    /**
     * Tipos que no llevan existencia: ningún documento de mercancía los mueve,
     * ni el kardex los registra. Se facturan y se cobran como cualquier otro.
     *
     * @var array<int, string>
     */
    public const NON_STOCKED_TYPES = ['service', 'non_inventoried'];

    public const COST_METHODS = ['average', 'fifo', 'standard'];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'sku',
        'barcode',
        'name',
        'description',
        'type',
        'category_id',
        'sale_tax_id',
        'purchase_tax_id',
        'cost_method',
        'standard_cost',
        'average_cost',
        'min_price',
        'is_purchasable',
        'is_sellable',
        'min_stock',
        'max_stock',
        'reorder_quantity',
        'weight',
        'volume',
        'image_path',
        'notes',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'standard_cost' => 'decimal:6',
            'average_cost' => 'decimal:6',
            'min_price' => 'decimal:6',
            'min_stock' => 'decimal:4',
            'max_stock' => 'decimal:4',
            'reorder_quantity' => 'decimal:4',
            'weight' => 'decimal:4',
            'volume' => 'decimal:4',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Si el artículo lleva existencia. Es la única respuesta a esa pregunta:
     * la tenían copiada media docena de servicios y una pantalla se quedó sin
     * ella, que es como los servicios acabaron pidiéndose en una entrada.
     */
    public function movesStock(): bool
    {
        return ! in_array($this->type, self::NON_STOCKED_TYPES, true);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(ItemUnit::class, 'item_id', 'id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ItemPrice::class, 'item_id', 'id');
    }

    protected static function newFactory(): ItemFactory
    {
        return ItemFactory::new();
    }
}
