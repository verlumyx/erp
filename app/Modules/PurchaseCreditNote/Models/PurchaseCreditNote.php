<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Models;

use App\Modules\Company\Models\Company;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\User\Models\User;
use Database\Factories\PurchaseCreditNoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseCreditNote extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_purchase_credit_notes';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'NCP';

    /** Con este tipo viaja la nota en `app_supplier_payment_applications`. */
    public const APPLICATION_SOURCE = 'credit_note';

    public const STATUSES = ['draft', 'confirmed', 'completed', 'cancelled'];

    public const REASONS = ['return', 'discount', 'price_correction', 'damaged', 'other'];

    /**
     * Transiciones permitidas. `completed` —la nota se agotó aplicándose a
     * facturas— y `cancelled` son terminales.
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
        'purchase_return_id',
        'supplier_document_number',
        'note_date',
        'reason',
        'reason_detail',
        'currency',
        'exchange_rate',
        'base_currency',
        'base_exchange_rate',
        'subtotal',
        'tax_amount',
        'total',
        'subtotal_ves',
        'tax_amount_ves',
        'total_ves',
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
            'note_date' => 'date',
            'exchange_rate' => 'decimal:8',
            'base_exchange_rate' => 'decimal:8',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'subtotal_ves' => 'decimal:2',
            'tax_amount_ves' => 'decimal:2',
            'total_ves' => 'decimal:2',
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

    /** Factura afectada por la nota; vacía en una nota sin factura previa. */
    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id', 'id');
    }

    /** Devolución que origina la nota; vacía en una nota sin devolución. */
    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseCreditNoteLine::class, 'purchase_credit_note_id', 'id');
    }

    protected static function newFactory(): PurchaseCreditNoteFactory
    {
        return PurchaseCreditNoteFactory::new();
    }
}
