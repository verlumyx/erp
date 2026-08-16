<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Database\Factories\ClientTypeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientType extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_client_types';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'TCL';

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'name',
        'description',
        'status',
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

    protected static function newFactory(): ClientTypeFactory
    {
        return ClientTypeFactory::new();
    }
}
