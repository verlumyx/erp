<?php

declare(strict_types=1);

namespace App\Modules\Client\Models;

use App\Modules\ClientType\Models\ClientType;
use App\Modules\Company\Models\Company;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\User\Models\User;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_clients';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'CLI';

    /**
     * Alias con el que el cliente viaja en las columnas `recipient_type` de los
     * documentos que se le dirigen. Ver `SharedServiceProvider::MORPH_MAP`.
     */
    public const MORPH_ALIAS = 'client';

    /** Letra del RIF. `V`, `E` y `P` son naturales; `J`, `G` y `C` jurídicas. */
    public const DOCUMENT_TYPES = ['V', 'E', 'J', 'P', 'G', 'C'];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'client_type_id',
        'price_list_id',
        'name',
        'legal_name',
        'document_type',
        'document_number',
        'phone',
        'mobile',
        'email',
        'address',
        'city',
        'state',
        'country',
        'payment_term_days',
        'credit_limit',
        'credit_blocked',
        'current_balance',
        'advance_balance',
        'discount_percent',
        'salesperson_id',
        'route_id',
        'latitude',
        'longitude',
        'status',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_term_days' => 'integer',
            'credit_limit' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'advance_balance' => 'decimal:2',
            'discount_percent' => 'decimal:4',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function clientType(): BelongsTo
    {
        return $this->belongsTo(ClientType::class, 'client_type_id', 'id');
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id', 'id');
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class, 'client_id', 'id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(ClientAddress::class, 'client_id', 'id');
    }

    protected static function newFactory(): ClientFactory
    {
        return ClientFactory::new();
    }
}
