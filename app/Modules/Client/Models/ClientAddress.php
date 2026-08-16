<?php

declare(strict_types=1);

namespace App\Modules\Client\Models;

use App\Modules\Company\Models\Company;
use Database\Factories\ClientAddressFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientAddress extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_client_addresses';

    public $incrementing = false;

    protected $keyType = 'string';

    public const TYPES = ['billing', 'shipping'];

    protected $fillable = [
        'id',
        'company_id',
        'client_id',
        'type',
        'name',
        'address',
        'city',
        'state',
        'country',
        'route_id',
        'latitude',
        'longitude',
        'is_default',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    protected static function newFactory(): ClientAddressFactory
    {
        return ClientAddressFactory::new();
    }
}
