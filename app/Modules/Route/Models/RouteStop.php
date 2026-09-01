<?php

declare(strict_types=1);

namespace App\Modules\Route\Models;

use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\Company\Models\Company;
use Database\Factories\RouteStopFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * La visita a un cliente en una fecha concreta.
 */
class RouteStop extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_route_stops';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Cómo terminó la visita. Es un eje aparte del `status` de la fila: una
     * parada fallida sigue siendo una parada activa del día.
     *
     * @var array<int, string>
     */
    public const STOP_STATUSES = ['pending', 'arrived', 'completed', 'skipped', 'failed'];

    /**
     * Paradas que todavía deben algo: son las que impiden desactivar la ruta.
     *
     * @var array<int, string>
     */
    public const OPEN_STOP_STATUSES = ['pending', 'arrived'];

    /**
     * Resultados sin entrega. Explicar por qué no se visitó es obligatorio.
     *
     * @var array<int, string>
     */
    public const UNVISITED_STOP_STATUSES = ['skipped', 'failed'];

    /**
     * Una parada ya cerrada no se replanifica: lo que pasó, pasó.
     *
     * @var array<int, string>
     */
    public const CLOSED_STOP_STATUSES = ['completed', 'skipped', 'failed'];

    protected $fillable = [
        'id',
        'company_id',
        'route_id',
        'client_id',
        'client_address_id',
        'stop_date',
        'sequence',
        'estimated_arrival',
        'actual_arrival',
        'actual_departure',
        'stop_status',
        'skip_reason',
        'latitude',
        'longitude',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stop_date' => 'date',
            'sequence' => 'integer',
            'actual_arrival' => 'datetime',
            'actual_departure' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function clientAddress(): BelongsTo
    {
        return $this->belongsTo(ClientAddress::class, 'client_address_id', 'id');
    }

    /** La visita ya terminó, de una forma o de otra. */
    public function isClosed(): bool
    {
        return in_array($this->stop_status, self::CLOSED_STOP_STATUSES, true);
    }

    protected static function newFactory(): RouteStopFactory
    {
        return RouteStopFactory::new();
    }
}
