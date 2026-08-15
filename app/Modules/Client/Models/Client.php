<?php

declare(strict_types=1);

namespace App\Modules\Client\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_clients';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'CLI';

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'name',
        'phone',
        'email',
        'status',
        'notes',
        'created_by',
    ];

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    protected static function newFactory(): ClientFactory
    {
        return ClientFactory::new();
    }
}
