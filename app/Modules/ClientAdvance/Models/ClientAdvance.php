<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Models;

use App\Modules\Client\Models\Client;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\Company\Models\Company;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\User\Models\User;
use Database\Factories\ClientAdvanceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClientAdvance extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_client_advances';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'ANC';

    /** Con este tipo viaja el anticipo en `app_client_collection_applications`. */
    public const APPLICATION_SOURCE = 'advance';

    public const STATUSES = [
        'draft',
        'pending_confirmation',
        'confirmed',
        'partial',
        'completed',
        'cancelled',
    ];

    public const PAYMENT_METHODS = ['cash', 'transfer', 'check', 'card', 'other'];

    /**
     * Estados en los que el anticipo todavía es un papel: se edita y no ha
     * comprometido dinero.
     *
     * @var array<int, string>
     */
    public const EDITABLE_STATUSES = ['draft'];

    /**
     * Transiciones permitidas.
     *
     * Aprobar deja el anticipo *comprometido* (`pending_confirmation`) y crea
     * su cobro espejo; solo confirmar ese cobro lo vuelve *recibido*
     * (`confirmed`), y anularlo lo devuelve a `draft` para corregirlo.
     *
     * @var array<string, array<int, string>>
     */
    public const STATUS_TRANSITIONS = [
        'draft' => ['pending_confirmation', 'cancelled'],
        'pending_confirmation' => ['confirmed', 'draft', 'cancelled'],
        'confirmed' => ['partial', 'completed'],
        'partial' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    /**
     * Lo único que la pantalla puede pedir. El resto no es una decisión del
     * usuario: `confirmed` y la vuelta a `draft` los mueve el cobro espejo, y
     * `partial` / `completed` las aplicaciones a facturas.
     *
     * @var array<int, string>
     */
    public const REQUESTABLE_STATUSES = ['pending_confirmation', 'cancelled'];

    /**
     * Estados desde los que el anticipo ya no puede anularse solo: o
     * comprometió dinero que hay que devolver por el cobro, o ya se aplicó.
     *
     * @var array<int, string>
     */
    public const CANCELLABLE_STATUSES = ['draft', 'pending_confirmation'];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'client_id',
        'sales_order_id',
        'origin_collection_id',
        'advance_date',
        'payment_method',
        'reference',
        'bank_account',
        'currency',
        'exchange_rate',
        'base_currency',
        'base_exchange_rate',
        'amount',
        'applied_amount',
        'balance',
        'refunded_amount',
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
            'advance_date' => 'date',
            'exchange_rate' => 'decimal:8',
            'base_exchange_rate' => 'decimal:8',
            'amount' => 'decimal:2',
            'applied_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
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
     * El cobro que lo hizo nacer, cuando el anticipo salió del excedente de un
     * `COB` en vez de capturarse a mano (`docs/ventas.md` §6.2).
     */
    public function originCollection(): BelongsTo
    {
        return $this->belongsTo(ClientCollection::class, 'origin_collection_id', 'id');
    }

    /** Pedido que motiva el anticipo; vacío en un anticipo sin pedido previo. */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * El cobro espejo vivo: el que nació al aprobar el anticipo y todavía no se
     * ha anulado. Un anticipo no puede tener más de uno.
     */
    public function collection(): HasOne
    {
        return $this->hasOne(ClientCollection::class, 'origin_id', 'id')
            ->where('origin_type', ClientCollection::ORIGIN_ADVANCE)
            ->where('status', '!=', 'cancelled');
    }

    protected static function newFactory(): ClientAdvanceFactory
    {
        return ClientAdvanceFactory::new();
    }
}
