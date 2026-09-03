<?php

declare(strict_types=1);

namespace App\Modules\Store\Models;

use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\Company\Models\Company;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\User\Models\User;
use Database\Factories\StoreOrderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pedido web: la bandeja de entrada de la tienda. No afecta inventario,
 * saldos ni clientes; un usuario del ERP lo convierte en orden de venta o lo
 * rechaza. No se edita.
 */
class StoreOrder extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_store_orders';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'PWE';

    public const STATUSES = ['pending', 'converted', 'rejected'];

    /**
     * Solo `pending` transiciona; los otros dos son terminales.
     *
     * @var array<string, array<int, string>>
     */
    public const STATUS_TRANSITIONS = [
        'pending' => ['converted', 'rejected'],
        'converted' => [],
        'rejected' => [],
    ];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'store_customer_id',
        'client_id',
        'client_address_id',
        'sales_order_id',
        'buyer_name',
        'buyer_document_type',
        'buyer_document_number',
        'buyer_email',
        'buyer_phone',
        'delivery_address',
        'delivery_city',
        'delivery_state',
        'currency',
        'exchange_rate',
        'subtotal',
        'total',
        'buyer_notes',
        'converted_by',
        'converted_at',
        'rejected_at',
        'rejection_reason',
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
            'exchange_rate' => 'decimal:8',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'converted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function storeCustomer(): BelongsTo
    {
        return $this->belongsTo(StoreCustomer::class, 'store_customer_id', 'id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function clientAddress(): BelongsTo
    {
        return $this->belongsTo(ClientAddress::class, 'client_address_id', 'id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id');
    }

    public function converter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StoreOrderLine::class, 'store_order_id', 'id')->orderBy('line_number');
    }

    protected static function newFactory(): StoreOrderFactory
    {
        return StoreOrderFactory::new();
    }
}
