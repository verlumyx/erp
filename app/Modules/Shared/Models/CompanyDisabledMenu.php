<?php

declare(strict_types=1);

namespace App\Modules\Shared\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Menu\Models\Menu;
use Database\Factories\CompanyDisabledMenuFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Un menú escondido a una empresa. Vive en Shared, como UserCompany, porque
 * lo leen los menús y lo escriben las empresas.
 */
class CompanyDisabledMenu extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_company_disabled_menus';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'menu_id',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }

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

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id', 'id');
    }

    protected static function newFactory(): CompanyDisabledMenuFactory
    {
        return CompanyDisabledMenuFactory::new();
    }
}
