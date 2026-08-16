<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Models;

use App\Modules\Company\Models\Company;
use Database\Factories\SupplierContactFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierContact extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_supplier_contacts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'supplier_id',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    protected static function newFactory(): SupplierContactFactory
    {
        return SupplierContactFactory::new();
    }
}
