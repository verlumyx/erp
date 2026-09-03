<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Models;

use App\Modules\Company\Models\Company;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\PurchaseInvoiceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PurchaseInvoice extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_purchase_invoices';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'FCO';

    public const STATUSES = ['draft', 'confirmed', 'completed', 'cancelled'];

    public const PAYMENT_STATUSES = ['pending', 'partial', 'paid', 'overdue'];

    /**
     * Estados en los que la factura ya generó su deuda y admite que se le
     * aplique un pago. En borrador todavía no debe nada.
     *
     * @var array<int, string>
     */
    public const PAYABLE_STATUSES = ['confirmed', 'completed'];

    /**
     * Alias del morph map admitidos como documento origen. Hoy solo la orden de
     * compra; mañana una solicitud o un contrato entran por aquí sin tocar la
     * tabla.
     *
     * @var array<int, string>
     */
    public const SOURCE_TYPES = [PurchaseOrder::MORPH_ALIAS];

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
        'supplier_id',
        'sourceable_type',
        'sourceable_id',
        'entry_id',
        'warehouse_id',
        'supplier_invoice_number',
        'supplier_invoice_series',
        'invoice_date',
        'received_date',
        'due_date',
        'currency',
        'exchange_rate',
        'base_currency',
        'base_exchange_rate',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'withholding_amount',
        'total',
        'subtotal_ves',
        'tax_amount_ves',
        'total_ves',
        'paid_amount',
        'balance',
        'payment_status',
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
            'received_date' => 'date',
            'due_date' => 'date',
            'exchange_rate' => 'decimal:8',
            'base_exchange_rate' => 'decimal:8',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'withholding_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'subtotal_ves' => 'decimal:2',
            'tax_amount_ves' => 'decimal:2',
            'total_ves' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Documento que originó la factura. Polimórfico para admitir mañana otros
     * orígenes sin agregar una columna por cada uno.
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceLine::class, 'purchase_invoice_id', 'id');
    }

    protected static function newFactory(): PurchaseInvoiceFactory
    {
        return PurchaseInvoiceFactory::new();
    }
}
