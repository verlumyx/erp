<?php

declare(strict_types=1);

namespace App\Modules\Company\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Shared\Models\CompanyDisabledMenu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_companies';

    public $incrementing = false;

    protected $keyType = 'string';

    public const TAXPAYER_TYPE_ORDINARY = 'ordinary';
    public const TAXPAYER_TYPE_FORMAL = 'formal';
    public const TAXPAYER_TYPE_SPECIAL = 'special';

    public const TAXPAYER_TYPES = [
        self::TAXPAYER_TYPE_ORDINARY,
        self::TAXPAYER_TYPE_FORMAL,
        self::TAXPAYER_TYPE_SPECIAL,
    ];

    protected $fillable = [
        'id',
        'name',
        'rif',
        'status',
        'address',
        'fiscal_address',
        'taxpayer_type',
        'description',
        'phone',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** Menús que esta empresa no ve. */
    public function disabledMenus(): HasMany
    {
        return $this->hasMany(CompanyDisabledMenu::class, 'company_id', 'id');
    }

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }
}
