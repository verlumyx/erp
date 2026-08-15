<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\User\Models\User;
use Database\Factories\ManualTransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManualTransaction extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'app_manual_transactions';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'MTX';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Statuses válidos. El flujo permitido es: pending → approved, pending → cancelled.
     */
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_CANCELLED];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'date',
        'payment_method',
        'reference',
        'currency',
        'description',
        'notes',
        'recorded_by',
        'total',
        'status',
        'approved_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total' => 'decimal:2',
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

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by', 'id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ManualTransactionLine::class, 'manual_transaction_id', 'id');
    }

    /**
     * Movimientos contables generados al aprobar (uno por línea).
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'related', 'related_type', 'related_id');
    }

    /**
     * @param  Builder<ManualTransaction>  $query
     * @return Builder<ManualTransaction>
     */
    public function scopeOfCompany(Builder $query, string $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Solo una transacción pendiente puede aprobarse o cancelarse.
     */
    public function canBeApproved(): bool
    {
        return $this->isPending();
    }

    public function canBeCancelled(): bool
    {
        return $this->isPending();
    }

    protected static function newFactory(): ManualTransactionFactory
    {
        return ManualTransactionFactory::new();
    }
}
