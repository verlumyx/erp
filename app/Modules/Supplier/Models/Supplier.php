<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Models;

use App\Modules\Company\Models\Company;
use App\Modules\SupplierType\Models\SupplierType;
use App\Modules\User\Models\User;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_suppliers';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'PRO';

    /**
     * Alias con el que el proveedor viaja como destinatario de un despacho: la
     * devolución de compra le manda la mercancía de vuelta.
     */
    public const MORPH_ALIAS = 'supplier';

    /** Letra del RIF. `V`, `E` y `P` son naturales; `J`, `G` y `C` jurídicas. */
    public const DOCUMENT_TYPES = ['V', 'E', 'J', 'P', 'G', 'C'];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'supplier_type_id',
        'name',
        'legal_name',
        'document_type',
        'document_number',
        'email',
        'phone',
        'mobile',
        'website',
        'address',
        'city',
        'state',
        'country',
        'currency',
        'payment_term_days',
        'credit_limit',
        'current_balance',
        'advance_balance',
        'lead_time_days',
        'notes',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_term_days' => 'integer',
            'lead_time_days' => 'integer',
            'credit_limit' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'advance_balance' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function supplierType(): BelongsTo
    {
        return $this->belongsTo(SupplierType::class, 'supplier_type_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class, 'supplier_id', 'id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(SupplierAddress::class, 'supplier_id', 'id');
    }

    protected static function newFactory(): SupplierFactory
    {
        return SupplierFactory::new();
    }
}
