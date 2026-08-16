<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Database\Factories\ExchangeRateFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_exchange_rates';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'TAS';

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'currency',
        'rate_date',
        'rate',
        'type',
        'source',
        'description',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Stored as a plain date (no time component) so the unique key
     * company + currency + date + type compares reliably on every driver.
     */
    protected function rateDate(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?Carbon => $value === null ? null : Carbon::parse($value)->startOfDay(),
            set: fn (Carbon|string $value): string => Carbon::parse($value)->toDateString(),
        );
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    protected static function newFactory(): ExchangeRateFactory
    {
        return ExchangeRateFactory::new();
    }
}
