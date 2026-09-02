<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\User\Models\User;
use Database\Factories\SupplierPaymentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPayment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_supplier_payments';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'PGP';

    /** Con este tipo viaja el pago en `app_supplier_payment_applications`. */
    public const APPLICATION_SOURCE = 'payment';

    public const STATUSES = ['draft', 'confirmed', 'completed', 'cancelled'];

    public const ORIGIN_TYPES = ['supplier', 'invoice', 'advance'];

    /** Origen del pago espejo de un anticipo: lo genera aprobarlo. */
    public const ORIGIN_ADVANCE = 'advance';

    /**
     * Orígenes que ofrece la pantalla al crear. `advance` queda fuera: esos
     * pagos nacen al aprobar un anticipo, nunca desde aquí.
     *
     * @var array<int, string>
     */
    public const CREATABLE_ORIGIN_TYPES = ['supplier', 'invoice'];

    public const PAYMENT_METHODS = ['cash', 'transfer', 'check', 'card', 'advance', 'credit_note', 'other'];

    /**
     * Formas de pago que no sacan dinero: cancelan la factura con un saldo a
     * favor que ya existe. Exigen `credit_source_id` —de qué documento sale ese
     * crédito— y escriben el reparto con su propio `source_type`.
     *
     * @var array<string, string>
     */
    public const CREDIT_METHODS = [
        'advance' => 'advance',
        'credit_note' => 'credit_note',
    ];

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
        'origin_type',
        'origin_id',
        'credit_source_id',
        'payment_date',
        'payment_method',
        'reference',
        'bank_account',
        'currency',
        'exchange_rate',
        'base_currency',
        'base_exchange_rate',
        'amount',
        'withholding_amount',
        'applied_amount',
        'unapplied_amount',
        'amount_ves',
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
            'payment_date' => 'date',
            'exchange_rate' => 'decimal:8',
            'base_exchange_rate' => 'decimal:8',
            'amount' => 'decimal:2',
            'withholding_amount' => 'decimal:2',
            'applied_amount' => 'decimal:2',
            'unapplied_amount' => 'decimal:2',
            'amount_ves' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Con qué `source_type` viaja este pago en el reparto: el suyo cuando sale
     * dinero, y el del crédito que lo respalda cuando no.
     */
    public function applicationSource(): string
    {
        return self::CREDIT_METHODS[$this->payment_method] ?? self::APPLICATION_SOURCE;
    }

    /** El documento cuyo crédito gasta este pago; su propio id si saca dinero. */
    public function applicationSourceId(): string
    {
        return $this->fundedByCredit() ? (string) $this->credit_source_id : $this->id;
    }

    /** Un pago que no saca dinero: lo respalda un anticipo o una nota. */
    public function fundedByCredit(): bool
    {
        return isset(self::CREDIT_METHODS[$this->payment_method]) && filled($this->credit_source_id);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * El reparto del pago entre facturas. La tabla es compartida con anticipos
     * y notas de crédito, así que la relación acota también por `source_type`.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(SupplierPaymentApplication::class, 'source_id', 'id')
            ->where('source_type', self::APPLICATION_SOURCE);
    }

    protected static function newFactory(): SupplierPaymentFactory
    {
        return SupplierPaymentFactory::new();
    }
}
