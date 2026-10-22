<?php

declare(strict_types=1);

namespace App\Modules\Tax\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Database\Factories\TaxFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tax extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_taxes';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'IMP';

    public const TYPE_GENERAL = 'general';
    public const TYPE_REDUCED = 'reduced';
    public const TYPE_ADDITIONAL = 'additional';
    public const TYPE_EXEMPT = 'exempt';

    public const TAX_TYPES = [
        self::TYPE_GENERAL,
        self::TYPE_REDUCED,
        self::TYPE_ADDITIONAL,
        self::TYPE_EXEMPT,
    ];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'name',
        'description',
        'percentage',
        'tax_type',
        'has_withholding',
        'withholding_percentage',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:4',
            'withholding_percentage' => 'decimal:4',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function isExempt(): bool
    {
        return $this->tax_type === self::TYPE_EXEMPT || (float) $this->percentage === 0.0;
    }

    public function getRateAttribute(): ?float
    {
        return $this->percentage !== null ? (float) $this->percentage : null;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    protected static function newFactory(): TaxFactory
    {
        return TaxFactory::new();
    }
}
