<?php

declare(strict_types=1);

namespace App\Modules\Entry\Models;

use App\Modules\Company\Models\Company;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\EntryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Entry extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_entries';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'ENT';

    public const STATUSES = ['draft', 'confirmed', 'completed', 'cancelled'];

    public const TYPES = ['purchase', 'production', 'return', 'donation', 'initial', 'transfer', 'other'];

    /** Tipo que llega desde otra bodega propia: no hay proveedor detrás. */
    public const TRANSFER_TYPE = 'transfer';

    /** Tipo que exige proveedor: lo que se compra viene de alguien. */
    public const SUPPLIER_TYPE = 'purchase';

    /**
     * Carga del inventario inicial. No tiene proveedor ni documento origen, y
     * solo se admite una vez por artículo y bodega.
     */
    public const INITIAL_TYPE = 'initial';

    public const INSPECTION_STATUSES = ['pending', 'approved', 'rejected', 'partial'];

    /**
     * Documentos que hoy pueden originar una entrada: la orden que la compró,
     * o el traslado que mandó la mercancía desde otra bodega propia.
     *
     * La entrada de un traslado cuelga del traslado y no del despacho que la
     * generó: lo que hay que poder reconocer de un vistazo es qué originó el
     * movimiento. El despacho sigue trazado línea a línea.
     */
    public const SOURCE_TYPES = [PurchaseOrder::MORPH_ALIAS, Transfer::MORPH_ALIAS];

    /** Alias con el que el kardex reconoce a la entrada como origen. */
    public const MOVEMENT_ORIGIN_TYPE = 'entry';

    /**
     * Estados en los que la mercancía ya entró a la bodega. Confirmar es lo que
     * la mete; anular desde aquí es lo que la vuelve a sacar.
     *
     * @var array<int, string>
     */
    public const POSTED_STATUSES = ['confirmed', 'completed'];

    /**
     * Transiciones permitidas. `completed` —la entrada ya está cerrada— y
     * `cancelled` son terminales.
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
        'supplier_id',
        'sourceable_type',
        'sourceable_id',
        'warehouse_id',
        'entry_date',
        'entry_type',
        'supplier_document',
        'carrier',
        'tracking_number',
        'received_by',
        'inspected_by',
        'inspection_status',
        'currency',
        'exchange_rate',
        'base_currency',
        'base_exchange_rate',
        'total_quantity',
        'total_cost',
        'is_invoiced',
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
            'entry_date' => 'date',
            'exchange_rate' => 'decimal:8',
            'base_exchange_rate' => 'decimal:8',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    /**
     * Documento que origina la entrada. No es un FK: es una relación
     * polimórfica, para que mañana admita otros documentos —orden de
     * producción, devolución de cliente— sin una columna por cada uno.
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    /** Quién recibió físicamente la mercancía en la bodega. */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by', 'id');
    }

    /** Quién hizo el control de calidad de lo recibido. */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(EntryLine::class, 'entry_id', 'id');
    }

    /** La mercancía viene de otra bodega propia: detrás hay un traslado. */
    public function comesFromTransfer(): bool
    {
        return $this->entry_type === self::TRANSFER_TYPE;
    }

    /** El documento que originó el movimiento es un traslado. */
    public function servesTransfer(): bool
    {
        return $this->sourceable_type === Transfer::MORPH_ALIAS;
    }

    protected static function newFactory(): EntryFactory
    {
        return EntryFactory::new();
    }
}
