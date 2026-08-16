<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Models;

use App\Modules\Company\Models\Company;
use Database\Factories\SupplierAddressFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierAddress extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_supplier_addresses';

    public $incrementing = false;

    protected $keyType = 'string';

    public const TYPES = ['billing', 'pickup', 'warehouse'];

    protected $fillable = [
        'id',
        'company_id',
        'supplier_id',
        'type',
        'address',
        'city',
        'state',
        'country',
        'is_default',
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

    protected static function newFactory(): SupplierAddressFactory
    {
        return SupplierAddressFactory::new();
    }
}
