<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Entry\Models\Entry;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseOrder extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_purchase_orders';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'OCO';

    /**
     * Alias con el que la orden viaja en las columnas `sourceable_type` de
     * los documentos que origina. Se guarda el alias y no el FQCN para que
     * mover o renombrar esta clase no invalide los datos ya escritos.
     */
    public const MORPH_ALIAS = 'purchase_order';

    public const STATUSES = ['draft', 'confirmed', 'partial', 'completed', 'cancelled'];

    /**
     * Estados en los que el documento sigue vivo: todavía espera mercancía o
     * dinero. Es lo que impide desactivar un artículo o una bodega que alguno
     * de ellos está usando.
     *
     * @var array<int, string>
     */
    public const OPEN_STATUSES = ['draft', 'confirmed', 'partial'];

    /**
     * Transiciones que alguien puede **pedir**. Son dos decisiones y nada más:
     * confirmar la orden y anularla.
     *
     * `partial` y `completed` no están aquí a propósito: el avance no se
     * declara, se calcula desde lo recibido y lo facturado, y lo escribe
     * `PurchaseOrderSettleStatusService` cuando la Entrada o la Factura de
     * compra mueven esas cuentas.
     *
     * `completed` y `cancelled` son terminales: una orden cerrada o anulada ya
     * no admite decisiones.
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
        'supplier_id',
        'warehouse_id',
        'order_date',
        'expected_date',
        'supplier_reference',
        'currency',
        'exchange_rate',
        'base_currency',
        'base_exchange_rate',
        'payment_term_days',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'received_percent',
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
            'received_percent' => 'decimal:4',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class, 'purchase_order_id', 'id');
    }

    /** Facturas que nacieron de esta orden. */
    public function purchaseInvoices(): MorphMany
    {
        return $this->morphMany(PurchaseInvoice::class, 'sourceable');
    }

    /** Entradas que reciben la mercancía de esta orden. */
    public function entries(): MorphMany
    {
        return $this->morphMany(Entry::class, 'sourceable');
    }

    protected static function newFactory(): PurchaseOrderFactory
    {
        return PurchaseOrderFactory::new();
    }
}
