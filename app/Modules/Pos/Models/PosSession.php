<?php

declare(strict_types=1);

namespace App\Modules\Pos\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_pos_sessions';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'COR';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_OPEN = 'confirmed';

    public const STATUS_CLOSED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_OPEN,
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'pos_register_id',
        'user_id',
        'opened_at',
        'closed_at',
        'opening_cash_usd',
        'opening_cash_ves',
        'expected_cash_usd',
        'expected_cash_ves',
        'expected_card_ves',
        'expected_mobile_ves',
        'expected_other',
        'declared_cash_usd',
        'declared_cash_ves',
        'declared_card_ves',
        'declared_mobile_ves',
        'declared_other',
        'difference_cash_usd',
        'difference_cash_ves',
        'sales_count',
        'cancelled_sales_count',
        'total_sales_amount',
        'total_discounts_amount',
        'supervisor_id',
        'closing_notes',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash_usd' => 'decimal:2',
            'opening_cash_ves' => 'decimal:2',
            'expected_cash_usd' => 'decimal:2',
            'expected_cash_ves' => 'decimal:2',
            'expected_card_ves' => 'decimal:2',
            'expected_mobile_ves' => 'decimal:2',
            'expected_other' => 'decimal:2',
            'declared_cash_usd' => 'decimal:2',
            'declared_cash_ves' => 'decimal:2',
            'declared_card_ves' => 'decimal:2',
            'declared_mobile_ves' => 'decimal:2',
            'declared_other' => 'decimal:2',
            'difference_cash_usd' => 'decimal:2',
            'difference_cash_ves' => 'decimal:2',
            'sales_count' => 'integer',
            'cancelled_sales_count' => 'integer',
            'total_sales_amount' => 'decimal:2',
            'total_discounts_amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(PosSale::class, 'pos_session_id', 'id');
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(PosCashMovement::class, 'pos_session_id', 'id');
    }

    public function denominations(): HasMany
    {
        return $this->hasMany(PosSessionDenomination::class, 'pos_session_id', 'id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }
}
