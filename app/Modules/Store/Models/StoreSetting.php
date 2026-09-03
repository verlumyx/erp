<?php

declare(strict_types=1);

namespace App\Modules\Store\Models;

use App\Modules\ClientType\Models\ClientType;
use App\Modules\Company\Models\Company;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\StoreSettingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Ajustes de la tienda en línea de una empresa. Singleton por empresa: no se
 * lista, no se crea a mano y no se desactiva.
 */
class StoreSetting extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_store_settings';

    public $incrementing = false;

    protected $keyType = 'string';

    /** Prefijo de la llave en claro, para reconocerla a simple vista. */
    public const KEY_PREFIX = 'stk_';

    protected $fillable = [
        'id',
        'company_id',
        'is_enabled',
        'store_name',
        'logo_path',
        'brand_color',
        'price_list_id',
        'warehouse_id',
        'shows_stock',
        'allows_orders',
        'default_client_type_id',
        'shows_secondary_currency',
        'contact_phone',
        'contact_email',
        'store_url',
        'api_key_hash',
        'api_key_last_used_at',
        'created_by',
    ];

    protected $hidden = [
        'api_key_hash',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'api_key_last_used_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function hasApiKey(): bool
    {
        return $this->api_key_hash !== null;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function defaultClientType(): BelongsTo
    {
        return $this->belongsTo(ClientType::class, 'default_client_type_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    protected static function newFactory(): StoreSettingFactory
    {
        return StoreSettingFactory::new();
    }
}
