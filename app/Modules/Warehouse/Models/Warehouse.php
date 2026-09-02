<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Database\Factories\WarehouseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Warehouse extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_warehouses';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'BOD';

    /**
     * Alias con el que la bodega viaja en las columnas `recipient_type`: es el
     * destinatario de un despacho que mueve mercancía entre bodegas propias.
     */
    public const MORPH_ALIAS = 'warehouse';

    /** Ubicación por defecto creada junto con la bodega. */
    public const DEFAULT_LOCATION_CODE = 'PRINCIPAL';

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'name',
        'type',
        'address',
        'phone',
        'city',
        'responsible_user_id',
        'is_default',
        'allows_negative_stock',
        'uses_locations',
        'is_sales_available',
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class, 'warehouse_id', 'id');
    }

    public function defaultLocation(): HasOne
    {
        return $this->hasOne(WarehouseLocation::class, 'warehouse_id', 'id')
            ->where('is_default', 'yes');
    }

    protected static function newFactory(): WarehouseFactory
    {
        return WarehouseFactory::new();
    }
}
