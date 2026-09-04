<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Models;

use App\Modules\Company\Models\Company;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\PurchaseReturnFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_purchase_returns';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'DVC';

    /**
     * Alias con el que la devolución viaja en `sourceable_type`: el despacho
     * que saca la mercancía de vuelta al proveedor cuelga de ella.
     */
    public const MORPH_ALIAS = 'purchase_return';

    public const STATUSES = ['draft', 'confirmed', 'completed', 'cancelled'];

    public const REASONS = ['damaged', 'wrong_item', 'expired', 'excess', 'quality', 'other'];

    /**
     * Estados en los que la devolución ya consumió cupo de la factura.
     * Confirmar es lo que lo consume; anular desde aquí es lo que lo libera.
     *
     * @var array<int, string>
     */
    public const POSTED_STATUSES = ['confirmed', 'completed'];

    /**
     * Transiciones permitidas. `completed` —la devolución ya está acreditada
     * con su nota de crédito— y `cancelled` son terminales.
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
        'purchase_invoice_id',
        'entry_id',
        'warehouse_id',
        'return_date',
        'reason',
        'reason_detail',
        'currency',
        'exchange_rate',
        'base_currency',
        'base_exchange_rate',
        'subtotal',
        'tax_amount',
        'total',
        'credit_note_id',
        'carrier',
        'tracking_number',
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
            'return_date' => 'date',
            'exchange_rate' => 'decimal:8',
            'base_exchange_rate' => 'decimal:8',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
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

    /** Factura de origen; vacía en una devolución sin factura previa. */
    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    /** Nota de crédito que acredita la devolución, si ya se emitió. */
    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(PurchaseCreditNote::class, 'credit_note_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseReturnLine::class, 'purchase_return_id', 'id');
    }

    protected static function newFactory(): PurchaseReturnFactory
    {
        return PurchaseReturnFactory::new();
    }
}
