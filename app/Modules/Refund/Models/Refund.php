<?php

declare(strict_types=1);

namespace App\Modules\Refund\Models;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\Sale\Models\Sale;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\User\Models\User;
use Database\Factories\RefundFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refund extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'app_refunds';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'REF';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Statuses válidos de un reembolso.
     */
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'sale_id',
        'client_id',
        'amount',
        'reason',
        'status',
        'requested_by',
        'resolved_by',
        'resolved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'resolved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by', 'id');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'related', 'related_type', 'related_id');
    }

    /**
     * @param  Builder<Refund>  $query
     * @return Builder<Refund>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * @param  Builder<Refund>  $query
     * @return Builder<Refund>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * @param  Builder<Refund>  $query
     * @return Builder<Refund>
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * @param  Builder<Refund>  $query
     * @return Builder<Refund>
     */
    public function scopeOfSale(Builder $query, string $saleId): Builder
    {
        return $query->where('sale_id', $saleId);
    }

    /**
     * Un reembolso solo es editable o resoluble mientras está pendiente.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    protected static function newFactory(): RefundFactory
    {
        return RefundFactory::new();
    }
}
