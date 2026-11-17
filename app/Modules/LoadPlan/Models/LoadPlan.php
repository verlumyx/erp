<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Route\Models\Route;
use App\Modules\User\Models\User;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\LoadPlanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoadPlan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_load_plans';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'PLC';

    public const STATUSES = ['draft', 'in_transit', 'confirmed', 'cancelled'];

    public const EDITABLE_STATUSES = ['draft'];

    public const STATUS_TRANSITIONS = [
        'draft' => ['in_transit', 'cancelled'],
        'in_transit' => ['confirmed', 'draft', 'cancelled'],
        'confirmed' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'plan_date',
        'route_id',
        'warehouse_id',
        'vehicle_id',
        'driver_name',
        'driver_id',
        'helper_name',
        'description',
        'total_dispatches',
        'total_weight',
        'total_volume',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'plan_date' => 'date',
            'total_dispatches' => 'integer',
            'total_weight' => 'decimal:4',
            'total_volume' => 'decimal:4',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function planDispatches(): HasMany
    {
        return $this->hasMany(LoadPlanDispatch::class, 'load_plan_id', 'id');
    }

    public function dispatches(): BelongsToMany
    {
        return $this->belongsToMany(
            Dispatch::class,
            'app_load_plan_dispatches',
            'load_plan_id',
            'dispatch_id',
        )->withPivot(['id', 'sequence', 'status'])->withTimestamps();
    }

    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE_STATUSES, true);
    }

    protected static function newFactory(): LoadPlanFactory
    {
        return LoadPlanFactory::new();
    }
}
