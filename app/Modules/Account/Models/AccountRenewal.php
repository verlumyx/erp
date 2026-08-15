<?php

declare(strict_types=1);

namespace App\Modules\Account\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Database\Factories\AccountRenewalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountRenewal extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_account_renewals';

    public $incrementing = false;

    protected $keyType = 'string';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_RENEWAL = 'renewal';

    /**
     * Tipos válidos de movimiento de renovación.
     */
    public const TYPES = ['purchase', 'renewal'];

    protected $fillable = [
        'id',
        'company_id',
        'account_id',
        'type',
        'amount',
        'period_start',
        'period_end',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'paid_at' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    protected static function newFactory(): AccountRenewalFactory
    {
        return AccountRenewalFactory::new();
    }
}
