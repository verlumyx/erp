<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Models;

use App\Modules\Company\Models\Company;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\User\Models\User;
use Database\Factories\SupplierAdvanceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupplierAdvance extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_supplier_advances';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'ANP';

    /** Con este tipo viaja el anticipo en `app_supplier_payment_applications`. */
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
     * su pago espejo; solo confirmar ese pago lo vuelve *entregado*
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
     * usuario: `confirmed` y la vuelta a `draft` los mueve el pago espejo, y
     * `partial` / `completed` las aplicaciones a facturas.
     *
     * @var array<int, string>
     */
    public const REQUESTABLE_STATUSES = ['pending_confirmation', 'cancelled'];

    /**
     * Estados desde los que el anticipo ya no puede anularse solo: o
     * comprometió dinero que hay que devolver por el pago, o ya se aplicó.
     *
     * @var array<int, string>
     */
    public const CANCELLABLE_STATUSES = ['draft', 'pending_confirmation'];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'supplier_id',
        'purchase_order_id',
        'origin_payment_id',
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

    /** Orden que motiva el anticipo; vacía en un anticipo sin orden previa. */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * El pago espejo vivo: el que nació al aprobar el anticipo y todavía no se
     * ha anulado. Un anticipo no puede tener más de uno.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(SupplierPayment::class, 'origin_id', 'id')
            ->where('origin_type', SupplierPayment::ORIGIN_ADVANCE)
            ->where('status', '!=', 'cancelled');
    }

    protected static function newFactory(): SupplierAdvanceFactory
    {
        return SupplierAdvanceFactory::new();
    }
}
