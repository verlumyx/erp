<?php

declare(strict_types=1);

namespace App\Modules\Client\Models;

use App\Modules\Company\Models\Company;
use Database\Factories\ClientContactFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientContact extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_client_contacts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'client_id',
        'name',
        'position',
        'email',
        'phone',
        'is_primary',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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

    protected static function newFactory(): ClientContactFactory
    {
        return ClientContactFactory::new();
    }
}
