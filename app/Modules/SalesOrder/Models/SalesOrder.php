<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Models;

use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\Company\Models\Company;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\SalesOrderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SalesOrder extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_sales_orders';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'OVE';

    /**
     * Alias del morph map con el que el pedido se referencia desde otros
     * documentos. Se guarda el alias y no el nombre de la clase para que
     * mover o renombrar esta clase no invalide los datos ya escritos.
     */
    public const MORPH_ALIAS = 'sales_order';

    public const STATUSES = ['draft', 'confirmed', 'partial', 'completed', 'cancelled'];

    /**
     * Estados en los que el documento sigue vivo: todavía espera mercancía o
     * dinero. Es lo que impide desactivar un artículo o una bodega que alguno
     * de ellos está usando.
     *
     * @var array<int, string>
     */
    public const OPEN_STATUSES = ['draft', 'confirmed', 'partial'];

    /** Solo un borrador se edita: confirmado ya reserva inventario y compromete crédito. */
    public const EDITABLE_STATUSES = ['draft'];

    /**
     * Transiciones que alguien puede **pedir**. Son dos decisiones y nada más:
     * confirmar el pedido y anularlo.
     *
     * `partial` y `completed` no están aquí a propósito: el avance no se
     * declara, se calcula desde lo despachado y lo facturado, y lo escribe
     * `SalesOrderSettleStatusService` cuando el Despacho o la Factura de venta
     * mueven esas cuentas.
     *
     * Un pedido anulado o cumplido es final.
     *
     * @var array<string, array<int, string>>
     */
    public const STATUS_TRANSITIONS = [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['cancelled'],
        'partial' => ['cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'client_id',
        'client_address_id',
        'warehouse_id',
        'price_list_id',
        'salesperson_id',
        'route_id',
        'order_date',
        'expected_date',
        'client_reference',
        'currency',
        'exchange_rate',
        'base_currency',
        'base_exchange_rate',
        'payment_term_days',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'dispatched_percent',
        'invoiced_percent',
        'approved_by',
        'approved_at',
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
            'order_date' => 'date',
            'expected_date' => 'date',
            'exchange_rate' => 'decimal:8',
            'base_exchange_rate' => 'decimal:8',
            'payment_term_days' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'dispatched_percent' => 'decimal:4',
            'invoiced_percent' => 'decimal:4',
            'approved_at' => 'datetime',
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

    public function clientAddress(): BelongsTo
    {
        return $this->belongsTo(ClientAddress::class, 'client_address_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id', 'id');
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id', 'id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class, 'sales_order_id', 'id');
    }

    /**
     * Facturas emitidas contra este pedido.
     *
     * La factura no apunta al pedido con un FK: lo hace con la relación
     * polimórfica `sourceable`, de modo que el mismo par de columnas admita
     * mañana otros documentos de origen.
     */
    public function salesInvoices(): MorphMany
    {
        return $this->morphMany(SalesInvoice::class, 'sourceable');
    }

    /**
     * Despachos emitidos contra este pedido. Mismo mecanismo que las facturas:
     * el despacho no apunta al pedido con un FK, sino con `sourceable`.
     */
    public function dispatches(): MorphMany
    {
        return $this->morphMany(Dispatch::class, 'sourceable');
    }

    protected static function newFactory(): SalesOrderFactory
    {
        return SalesOrderFactory::new();
    }
}
