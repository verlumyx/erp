<?php

declare(strict_types=1);

namespace App\Modules\Route\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\RouteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_routes';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'RUT';

    /**
     * La ruta es un maestro, no un documento: no se confirma ni se anula, se
     * activa y se desactiva.
     *
     * @var array<int, string>
     */
    public const STATUSES = ['active', 'inactive'];

    /**
     * Para qué se recorre. `mixed` entrega y cobra en la misma visita.
     *
     * @var array<int, string>
     */
    public const TYPES = ['delivery', 'collection', 'sales', 'mixed'];

    /**
     * @var array<int, string>
     */
    public const FREQUENCIES = ['daily', 'weekly', 'biweekly', 'monthly', 'on_demand'];

    /**
     * Días de ejecución admitidos en `weekdays`.
     *
     * @var array<int, string>
     */
    public const WEEKDAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'name',
        'description',
        'type',
        'warehouse_id',
        'driver_id',
        'salesperson_id',
        'vehicle_plate',
        'vehicle_capacity_weight',
        'vehicle_capacity_volume',
        'frequency',
        'weekdays',
        'zone',
        'city',
        'estimated_duration_minutes',
        'estimated_distance_km',
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
            'weekdays' => 'array',
            'vehicle_capacity_weight' => 'decimal:4',
            'vehicle_capacity_volume' => 'decimal:4',
            'estimated_duration_minutes' => 'integer',
            'estimated_distance_km' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /** Bodega de la que sale la carga del día. */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id', 'id');
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** La plantilla: a quién le toca, y en qué orden. */
    public function clients(): HasMany
    {
        return $this->hasMany(RouteClient::class, 'route_id', 'id');
    }

    /** La ejecución: qué pasó cada día. */
    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class, 'route_id', 'id');
    }

    /**
     * El vehículo declaró lo que aguanta. En cero no hay límite contra el que
     * comparar, así que la planificación no puede rechazar nada por peso.
     */
    public function hasDeclaredWeightCapacity(): bool
    {
        return (float) $this->vehicle_capacity_weight > 0;
    }

    public function hasDeclaredVolumeCapacity(): bool
    {
        return (float) $this->vehicle_capacity_volume > 0;
    }

    protected static function newFactory(): RouteFactory
    {
        return RouteFactory::new();
    }
}
