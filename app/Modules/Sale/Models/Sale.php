<?php

declare(strict_types=1);

namespace App\Modules\Sale\Models;

use App\Modules\Account\Models\Profile;
use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\Plan\Models\Plan;
use App\Modules\Service\Models\Service;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\User\Models\User;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'app_sales';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'SAL';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Statuses válidos de una venta.
     */
    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_EXPIRED, self::STATUS_CANCELLED];

    public const CAPACITY_PROFILE = 'profile';

    public const CAPACITY_FULL_ACCOUNT = 'full_account';

    /**
     * Capacidades válidas (heredadas del plan vía snapshot).
     */
    public const CAPACITIES = [self::CAPACITY_PROFILE, self::CAPACITY_FULL_ACCOUNT];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'client_id',
        'plan_id',
        'agent_id',
        'service_id',
        'capacity',
        'duration_days',
        'price',
        'start_date',
        'end_date',
        'status',
        'cancelled_at',
        'cancellation_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'price' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id', 'id');
    }

    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class, 'app_sale_profiles', 'sale_id', 'profile_id')
            ->withPivot('created_at');
    }

    public function saleProfiles(): HasMany
    {
        return $this->hasMany(SaleProfile::class, 'sale_id', 'id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(SaleRenewal::class, 'sale_id', 'id');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'related', 'related_type', 'related_id');
    }

    /**
     * @param  Builder<Sale>  $query
     * @return Builder<Sale>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * @param  Builder<Sale>  $query
     * @return Builder<Sale>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    /**
     * @param  Builder<Sale>  $query
     * @return Builder<Sale>
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Ventas activas que vencen entre hoy y hoy+N días.
     *
     * @param  Builder<Sale>  $query
     * @return Builder<Sale>
     */
    public function scopeExpiringSoon(Builder $query, int $days = 7): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    /**
     * @param  Builder<Sale>  $query
     * @return Builder<Sale>
     */
    public function scopeOfAgent(Builder $query, string $userId): Builder
    {
        return $query->where('agent_id', $userId);
    }

    /**
     * @param  Builder<Sale>  $query
     * @return Builder<Sale>
     */
    public function scopeOfClient(Builder $query, string $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * @param  Builder<Sale>  $query
     * @return Builder<Sale>
     */
    public function scopeOfService(Builder $query, string $serviceId): Builder
    {
        return $query->where('service_id', $serviceId);
    }

    /**
     * Una venta expirada está en periodo de gracia si su vencimiento todavía no
     * supera los días de gracia configurados (config('sales.grace_period_days')).
     */
    public function isInGracePeriod(): bool
    {
        if ($this->status !== self::STATUS_EXPIRED) {
            return false;
        }

        $graceDays = (int) config('sales.grace_period_days');

        return $this->end_date->copy()->addDays($graceDays)->startOfDay()->gte(now()->startOfDay());
    }

    /**
     * Se puede renovar si está activa, o expirada dentro del periodo de gracia.
     */
    public function canBeRenewed(): bool
    {
        return $this->status === self::STATUS_ACTIVE || $this->isInGracePeriod();
    }

    /**
     * Se reactiva cuando está cancelada, o expirada fuera del periodo de gracia.
     */
    public function canBeReactivated(): bool
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return true;
        }

        return $this->status === self::STATUS_EXPIRED && ! $this->isInGracePeriod();
    }

    /**
     * Días que faltan para el vencimiento (negativo si ya venció).
     */
    public function daysUntilExpiration(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->end_date->copy()->startOfDay(), false);
    }

    protected static function newFactory(): SaleFactory
    {
        return SaleFactory::new();
    }
}
