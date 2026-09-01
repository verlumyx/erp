<?php

declare(strict_types=1);

namespace App\Modules\Route\Models;

use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\Company\Models\Company;
use Database\Factories\RouteClientFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un cliente fijo de la ruta. Es la plantilla, no la visita: aquí no hay fecha.
 */
class RouteClient extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_route_clients';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'route_id',
        'client_id',
        'client_address_id',
        'sequence',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
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

    protected static function newFactory(): RouteClientFactory
    {
        return RouteClientFactory::new();
    }
}
