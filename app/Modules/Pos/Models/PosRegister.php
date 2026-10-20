<?php

declare(strict_types=1);

namespace App\Modules\Pos\Models;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PosRegister extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_pos_registers';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'CAJ';

    public const STATUSES = ['active', 'inactive'];

    public const EDITABLE_STATUSES = ['active'];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'name',
        'warehouse_id',
        'price_list_id',
        'default_client_id',
        'receipt_printer_name',
        'invoice_series',
        'allow_price_override',
        'allow_custom_discounts',
        'max_discount_percent',
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
            'max_discount_percent' => 'decimal:4',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id', 'id');
    }

    public function defaultClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'default_client_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class, 'pos_register_id', 'id');
    }

    public function activeSession(): HasOne
    {
        return $this->hasOne(PosSession::class, 'pos_register_id', 'id')
            ->where('status', 'confirmed');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'active' && ! $this->activeSession()->exists();
    }
}
