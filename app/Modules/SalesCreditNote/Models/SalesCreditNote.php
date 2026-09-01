<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Models;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\User\Models\User;
use Database\Factories\SalesCreditNoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesCreditNote extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_sales_credit_notes';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'NCC';

    /** Con qué alias firma sus asientos en el kardex. */
    public const MOVEMENT_ORIGIN_TYPE = 'sales_credit_note';

    public const STATUSES = ['draft', 'confirmed', 'completed', 'cancelled'];

    public const REASONS = ['return', 'discount', 'price_correction', 'damaged', 'cancellation', 'other'];

    /** Una nota confirmada ya bajó la cuenta por cobrar: no se edita. */
    public const EDITABLE_STATUSES = ['draft'];

    /**
     * Transiciones permitidas. `completed` —el crédito se agotó aplicándose a
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
        'client_id',
        'sales_invoice_id',
        'sales_return_id',
        'note_series',
        'note_number',
        'note_date',
        'reason',
        'reason_detail',
        'affects_inventory',
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
        'fiscal_status',
        'fiscal_uuid',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    /** Factura afectada por la nota; vacía en una nota sin factura previa. */
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesCreditNoteLine::class, 'sales_credit_note_id', 'id');
    }

    protected static function newFactory(): SalesCreditNoteFactory
    {
        return SalesCreditNoteFactory::new();
    }
}
