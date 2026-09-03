<?php

declare(strict_types=1);

namespace App\Modules\Store\Models;

use App\Modules\Company\Models\Company;
use Database\Factories\StoreItemImageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Una foto de la galería de la publicación. Tabla de detalle: quitar una
 * foto es desactivarla.
 */
class StoreItemImage extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_store_item_images';

    public $incrementing = false;

    protected $keyType = 'string';

    /** Disco donde viven las fotos; se sirven por `/storage/...`. */
    public const DISK = 'public';

    protected $fillable = [
        'id',
        'company_id',
        'store_item_id',
        'path',
        'alt_text',
        'order',
        'width',
        'height',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** URL absoluta, con `APP_URL`, para que la tienda no tenga que armarla. */
    public function url(): string
    {
        return Storage::disk(self::DISK)->url($this->path);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function storeItem(): BelongsTo
    {
        return $this->belongsTo(StoreItem::class, 'store_item_id', 'id');
    }

    protected static function newFactory(): StoreItemImageFactory
    {
        return StoreItemImageFactory::new();
    }
}
