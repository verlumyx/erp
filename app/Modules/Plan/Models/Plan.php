<?php

declare(strict_types=1);

namespace App\Modules\Plan\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Service\Models\Service;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_plans';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'PLA';

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'service_id',
        'name',
        'capacity',
        'duration_days',
        'sale_price',
        'roi_target_pct',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'sale_price' => 'decimal:2',
            'roi_target_pct' => 'decimal:2',
            'active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    protected static function newFactory(): PlanFactory
    {
        return PlanFactory::new();
    }
}
