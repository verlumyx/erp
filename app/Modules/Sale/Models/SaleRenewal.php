<?php

declare(strict_types=1);

namespace App\Modules\Sale\Models;

use App\Modules\User\Models\User;
use Database\Factories\SaleRenewalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleRenewal extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_sale_renewals';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * El historial de renovaciones es inmutable; solo registra created_at.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'id',
        'sale_id',
        'renewed_at',
        'previous_end_date',
        'new_end_date',
        'duration_days',
        'price',
        'renewed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'renewed_at' => 'date',
            'previous_end_date' => 'date',
            'new_end_date' => 'date',
            'duration_days' => 'integer',
            'price' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'id');
    }

    public function renewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renewed_by', 'id');
    }

    protected static function newFactory(): SaleRenewalFactory
    {
        return SaleRenewalFactory::new();
    }
}
