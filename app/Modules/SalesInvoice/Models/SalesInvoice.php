<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Models;

use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\Company\Models\Company;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\SalesInvoiceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SalesInvoice extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_sales_invoices';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'FVE';

    public const STATUSES = ['draft', 'confirmed', 'completed', 'cancelled'];

    /** Una factura confirmada no se edita: se anula y se emite otra. */
    public const EDITABLE_STATUSES = ['draft'];

    /**
     * Estados en los que la factura ya generó su cuenta por cobrar y admite
     * que un cobro, un anticipo o una nota de crédito la abone. En borrador
     * todavía no debe nada; anulada ya no debe.
     *
     * @var array<int, string>
     */
    public const COLLECTIBLE_STATUSES = ['confirmed', 'completed'];

    /**
     * Alias del morph map admitidos como documento origen. Hoy solo la orden
     * de venta; mañana una cotización o un ticket de punto de venta entran
     * aquí sin tocar la tabla.
     *
     * @var array<int, string>
     */
    public const SOURCE_TYPES = [SalesOrder::MORPH_ALIAS];

    /** Alias admitidos como línea origen, en el mismo orden que arriba. */
    public const SOURCE_LINE_TYPES = [SalesOrderLine::MORPH_ALIAS];

    /**
     * Transiciones que alguien puede **pedir**: emitir la factura y anularla.
     *
     * `completed` no está aquí a propósito: cerrar la factura es haberla
     * cobrado, no declararlo. Lo escribe `SalesInvoiceSettleStatusService`
     * cuando un cobro, un anticipo o una nota de crédito la deja sin saldo.
     *
     * Una factura anulada o cobrada es final.
     *
     * @var array<string, array<int, string>>
     */
    public const STATUS_TRANSITIONS = [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['cancelled'],
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
        'dispatch_id',
        'client_address_id',
        'warehouse_id',
        'salesperson_id',
        'invoice_series',
        'invoice_number',
        'invoice_date',
        'due_date',
        'sale_type',
        'currency',
        'exchange_rate',
        'base_currency',
        'base_exchange_rate',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'withholding_amount',
        'total',
        'total_cost',
        'subtotal_ves',
        'tax_amount_ves',
        'total_ves',
        'paid_amount',
        'balance',
        'payment_status',
        'fiscal_status',
        'fiscal_uuid',
        'printed_at',
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
            'invoice_date' => 'date',
            'due_date' => 'date',
            'exchange_rate' => 'decimal:8',
            'base_exchange_rate' => 'decimal:8',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'withholding_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'subtotal_ves' => 'decimal:2',
            'tax_amount_ves' => 'decimal:2',
            'total_ves' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'printed_at' => 'datetime',
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

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Documento que originó la factura. Nulo en una factura directa.
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLine::class, 'sales_invoice_id', 'id');
    }

    protected static function newFactory(): SalesInvoiceFactory
    {
        return SalesInvoiceFactory::new();
    }
}
