<?php

declare(strict_types=1);

namespace App\Modules\Service\Models;

use App\Modules\Company\Models\Company;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_services';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'SER';

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'name',
        'logo_url',
        'max_profiles',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'max_profiles' => 'integer',
            'active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    protected static function newFactory(): ServiceFactory
    {
        return ServiceFactory::new();
    }
}
