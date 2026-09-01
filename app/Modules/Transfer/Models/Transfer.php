<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\TransferFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transfer extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_transfers';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'TRA';

    public const STATUSES = ['draft', 'confirmed', 'partial', 'completed', 'cancelled'];

    /** Un traslado confirmado ya sacó la mercancía: no se edita, se anula. */
    public const EDITABLE_STATUSES = ['draft'];

    /** Alias con el que el kardex reconoce al traslado como origen. */
    public const MOVEMENT_ORIGIN_TYPE = 'transfer';

    /**
     * Por qué se mueve la mercancía.
     *
     * @var array<int, string>
     */
    public const REASONS = ['restock', 'rebalance', 'damaged', 'quarantine', 'other'];

    /**
     * Estados en los que la mercancía ya salió de la bodega de origen.
     * Confirmar es lo que la saca; anular desde aquí es lo que la devuelve.
     *
     * @var array<int, string>
     */
    public const POSTED_STATUSES = ['confirmed', 'partial', 'completed'];

    /**
     * Dónde está la mercancía. Es un eje aparte de `status`.
     *
     * @var array<int, string>
     */
    public const TRANSFER_STATUSES = ['pending', 'in_transit', 'received', 'partial_received'];

    /**
     * Resultados con los que se cierra un viaje: la mercancía ya llegó al
     * destino, entera o a medias.
     *
     * @var array<int, string>
     */
    public const SETTLED_TRANSFER_STATUSES = ['received', 'partial_received'];

    /**
     * Transiciones permitidas. `partial` no se elige desde la pantalla de
     * estado: lo escribe la recepción cuando llega menos de lo que salió.
     *
     * @var array<string, array<int, string>>
     */
    public const STATUS_TRANSITIONS = [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'partial' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'origin_warehouse_id',
        'destination_warehouse_id',
        'transit_warehouse_id',
        'transfer_date',
        'expected_date',
        'received_date',
        'reason',
        'reason_detail',
        'driver_id',
        'vehicle_plate',
        'route_id',
        'total_quantity',
        'total_cost',
        'transfer_status',
        'sent_by',
        'received_by',
        'cancelled_at',
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
            'transfer_date' => 'date',
            'expected_date' => 'date',
            'received_date' => 'date',
            'total_quantity' => 'decimal:4',
            'total_cost' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function originWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id', 'id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id', 'id');
    }

    public function transitWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'transit_warehouse_id', 'id');
    }

    /** Quién condujo el viaje. */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id', 'id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by', 'id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TransferLine::class, 'transfer_id', 'id');
    }

    /**
     * El traslado viaja en dos pasos. Lo decide la bodega de tránsito: con ella
     * la mercancía sale hoy y llega después, y sin ella los dos movimientos son
     * simultáneos.
     */
    public function isTwoStep(): bool
    {
        return filled($this->transit_warehouse_id);
    }

    /** La mercancía ya llegó al destino, entera o a medias. */
    public function isReceiptSettled(): bool
    {
        return in_array($this->transfer_status, self::SETTLED_TRANSFER_STATUSES, true);
    }

    protected static function newFactory(): TransferFactory
    {
        return TransferFactory::new();
    }
}
