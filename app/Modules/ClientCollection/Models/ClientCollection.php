<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Models;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Database\Factories\ClientCollectionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientCollection extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_client_collections';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'COB';

    /** Con este tipo viaja el cobro en `app_client_collection_applications`. */
    public const APPLICATION_SOURCE = 'collection';

    public const STATUSES = ['draft', 'confirmed', 'completed', 'cancelled'];

    public const ORIGIN_TYPES = ['client', 'invoice', 'advance'];

    /** Origen del cobro espejo de un anticipo: lo genera aprobarlo. */
    public const ORIGIN_ADVANCE = 'advance';

    /**
     * Orígenes que ofrece la pantalla al crear. `advance` queda fuera: esos
     * cobros nacen al aprobar un anticipo, nunca desde aquí.
     *
     * @var array<int, string>
     */
    public const CREATABLE_ORIGIN_TYPES = ['client', 'invoice'];

    public const PAYMENT_METHODS = ['cash', 'transfer', 'check', 'card', 'advance', 'credit_note', 'other'];

    /** Ciclo del cheque, aparte del estado del documento. */
    public const CHECK_STATUSES = ['pending', 'deposited', 'cleared', 'bounced'];

    /** El cheque que no tiene fondos: revierte lo que el cobro había abonado. */
    public const CHECK_BOUNCED = 'bounced';

    /**
     * Transiciones permitidas. `completed` y `cancelled` son terminales.
     *
     * @var array<string, array<int, string>>
     */
    public const STATUS_TRANSITIONS = [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'client_id',
        'origin_type',
        'origin_id',
        'collection_date',
        'payment_method',
        'reference',
        'bank_account',
        'collected_by',
        'route_id',
        'currency',
        'exchange_rate',
        'base_currency',
        'base_exchange_rate',
        'amount',
        'withholding_amount',
        'applied_amount',
        'unapplied_amount',
        'amount_ves',
        'check_number',
        'check_date',
        'check_status',
        'cancelled_at',
        'cancellation_reason',
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
            'collection_date' => 'date',
            'check_date' => 'date',
            'exchange_rate' => 'decimal:8',
            'base_exchange_rate' => 'decimal:8',
            'amount' => 'decimal:2',
            'withholding_amount' => 'decimal:2',
            'applied_amount' => 'decimal:2',
            'unapplied_amount' => 'decimal:2',
            'amount_ves' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    /** Cobrador o vendedor que recibió el dinero. */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * El reparto del cobro entre facturas. La tabla es compartida con anticipos
     * y notas de crédito, así que la relación acota también por `source_type`.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(ClientCollectionApplication::class, 'source_id', 'id')
            ->where('source_type', self::APPLICATION_SOURCE);
    }

    protected static function newFactory(): ClientCollectionFactory
    {
        return ClientCollectionFactory::new();
    }
}
