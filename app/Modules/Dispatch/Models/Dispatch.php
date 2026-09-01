<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Models;

use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\Company\Models\Company;
use App\Modules\Route\Models\Route;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\DispatchFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Dispatch extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_dispatches';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'DES';

    public const STATUSES = ['draft', 'confirmed', 'completed', 'cancelled'];

    /** Un despacho confirmado ya sacó la mercancía: no se edita, se anula. */
    public const EDITABLE_STATUSES = ['draft'];

    /**
     * Alias del morph map admitidos como documento origen. Hoy solo el pedido
     * de venta; mañana un contrato de suministro o un traspaso a consignación
     * entran aquí sin tocar la tabla.
     *
     * @var array<int, string>
     */
    public const SOURCE_TYPES = [SalesOrder::MORPH_ALIAS];

    /** Alias admitidos como línea origen, en el mismo orden que arriba. */
    public const SOURCE_LINE_TYPES = [SalesOrderLine::MORPH_ALIAS];

    /** Alias con el que el kardex reconoce al despacho como origen. */
    public const MOVEMENT_ORIGIN_TYPE = 'dispatch';

    /**
     * Estados en los que la mercancía ya salió de la bodega. Confirmar es lo
     * que la saca; anular desde aquí es lo que la devuelve.
     *
     * @var array<int, string>
     */
    public const POSTED_STATUSES = ['confirmed', 'completed'];

    /**
     * Cómo terminó el viaje. Es un eje aparte de `status`: el documento se
     * confirma y se cumple, la mercancía se entrega, se rechaza o vuelve.
     *
     * @var array<int, string>
     */
    public const DELIVERY_STATUSES = [
        'pending', 'in_transit', 'delivered', 'partial_delivered', 'rejected', 'returned',
    ];

    /**
     * Resultados con los que se cierra un viaje. Son los que el registro de la
     * entrega puede escribir: los otros dos los pone el propio documento al
     * nacer y al confirmarse.
     *
     * @var array<int, string>
     */
    public const SETTLED_DELIVERY_STATUSES = ['delivered', 'partial_delivered', 'rejected', 'returned'];

    /** Resultados en los que el cliente no se quedó con nada. */
    public const REFUSED_DELIVERY_STATUSES = ['rejected', 'returned'];

    /**
     * Transiciones permitidas. `completed` —el viaje terminó y la entrega está
     * registrada— y `cancelled` son terminales.
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
        'sourceable_type',
        'sourceable_id',
        'client_address_id',
        'warehouse_id',
        'route_id',
        'route_stop_id',
        'dispatch_date',
        'delivery_date',
        'driver_id',
        'vehicle_plate',
        'carrier',
        'tracking_number',
        'freight_amount',
        'total_quantity',
        'total_weight',
        'total_volume',
        'total_cost',
        'delivery_status',
        'received_by_name',
        'received_by_document',
        'signature_path',
        'evidence_path',
        'latitude',
        'longitude',
        'rejection_reason',
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
            'dispatch_date' => 'date',
            'delivery_date' => 'date',
            'freight_amount' => 'decimal:2',
            'total_quantity' => 'decimal:4',
            'total_weight' => 'decimal:4',
            'total_volume' => 'decimal:4',
            'total_cost' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
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

    /**
     * Documento origen del despacho. No es un FK: el tipo guarda el alias del
     * morph map, así que renombrar la clase no invalida lo ya escrito.
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function clientAddress(): BelongsTo
    {
        return $this->belongsTo(ClientAddress::class, 'client_address_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    /** Quién condujo el viaje. */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id', 'id');
    }

    /**
     * Ruta por la que sale el despacho. Se llama `deliveryRoute` y no `route`
     * para no confundirse con las rutas HTTP en un modelo de Laravel.
     */
    public function deliveryRoute(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DispatchLine::class, 'dispatch_id', 'id');
    }

    /** La entrega ya está registrada: el viaje terminó de una forma o de otra. */
    public function isDeliverySettled(): bool
    {
        return in_array($this->delivery_status, self::SETTLED_DELIVERY_STATUSES, true);
    }

    protected static function newFactory(): DispatchFactory
    {
        return DispatchFactory::new();
    }
}
