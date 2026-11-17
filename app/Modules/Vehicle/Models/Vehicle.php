<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_vehicles';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'CAM';

    public const STATUSES = ['active', 'maintenance', 'inactive'];

    public const VEHICLE_TYPES = [
        'pickup' => 'Camioneta / Pick-up',
        'cava_350' => 'Camión 350 / Cava Liviana',
        'truck' => 'Camión Mediano / NPR / Cargo',
        'heavy_truck' => 'Camión Pesado / Gandola / Chuto',
        'other' => 'Otro vehículo',
    ];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'plate',
        'name',
        'brand',
        'model',
        'year',
        'vehicle_type',
        'capacity_weight',
        'capacity_volume',
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
            'year' => 'integer',
            'capacity_weight' => 'decimal:4',
            'capacity_volume' => 'decimal:4',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function hasDeclaredWeightCapacity(): bool
    {
        return (float) $this->capacity_weight > 0;
    }

    public function hasDeclaredVolumeCapacity(): bool
    {
        return (float) $this->capacity_volume > 0;
    }

    protected static function newFactory(): VehicleFactory
    {
        return VehicleFactory::new();
    }
}
