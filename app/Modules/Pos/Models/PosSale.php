<?php

declare(strict_types=1);

namespace App\Modules\Pos\Models;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_pos_sales';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'POS';

    public const MORPH_ALIAS = 'pos_sale';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'pos_session_id',
        'pos_register_id',
        'client_id',
        'sales_invoice_id',
        'dispatch_id',
        'sale_date',
        'currency',
        'exchange_rate',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'igtf_amount',
        'total',
        'total_in_local_currency',
        'paid_amount',
        'change_amount_usd',
        'change_amount_ves',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
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
            'sale_date' => 'datetime',
            'cancelled_at' => 'datetime',
            'exchange_rate' => 'decimal:8',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'igtf_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'total_in_local_currency' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount_usd' => 'decimal:2',
            'change_amount_ves' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id', 'id');
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id', 'id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id', 'id');
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class, 'dispatch_id', 'id');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PosSaleLine::class, 'pos_sale_id', 'id');
    }

    public function activeLines(): HasMany
    {
        return $this->hasMany(PosSaleLine::class, 'pos_sale_id', 'id')
            ->where('status', 'active');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class, 'pos_sale_id', 'id');
    }

    public function activePayments(): HasMany
    {
        return $this->hasMany(PosPayment::class, 'pos_sale_id', 'id')
            ->where('status', 'active');
    }
}
