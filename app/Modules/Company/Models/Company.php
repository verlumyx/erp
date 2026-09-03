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

    protected $fillable = [
        'id',
        'name',
        'status',
        'description',
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
