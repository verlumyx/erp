<?php

declare(strict_types=1);

namespace App\Modules\Store\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Item\Models\Item;
use App\Modules\User\Models\User;
use Database\Factories\StoreItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Publicación: un artículo del ERP tal como se muestra en la tienda. Lleva
 * solo lo que el maestro no tiene —textos comerciales, orden, fotos—; la
 * categoría, la unidad, el precio y la existencia se leen del artículo al
 * servir la API.
 */
class StoreItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_store_items';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'PUB';

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'item_id',
        'slug',
        'title',
        'summary',
        'description',
        'is_featured',
        'order',
        'published_at',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Lo que la tienda puede mostrar: publicación activa de un artículo activo
     * y vendible. Un artículo desactivado en el ERP deja de salir aunque su
     * publicación siga `active`; el filtro se hace aquí, no con un cascade.
     */
    public function scopeVisibleInStore(Builder $query): Builder
    {
        return $query
            ->where('app_store_items.status', 'active')
            ->whereHas('item', fn (Builder $item): Builder => $item
                ->where('status', 'active')
                ->where('is_sellable', 'yes'));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(StoreItemImage::class, 'store_item_id', 'id')->orderBy('order');
    }

    public function activeImages(): HasMany
    {
        return $this->images()->where('status', 'active');
    }

    protected static function newFactory(): StoreItemFactory
    {
        return StoreItemFactory::new();
    }
}
