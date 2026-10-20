<?php

declare(strict_types=1);

namespace App\Modules\Pos\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosCashMovement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_pos_cash_movements';

    public $incrementing = false;

    protected $keyType = 'string';

    public const MOVEMENT_TYPES = ['in', 'out'];

    public const CONCEPTS = [
        'change_replenishment',
        'cash_drop',
        'expense',
        'other',
    ];

    protected $fillable = [
        'id',
        'company_id',
        'pos_session_id',
        'movement_type',
        'concept',
        'currency',
        'exchange_rate',
        'amount',
        'reason',
        'authorized_by',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:8',
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id', 'id');
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
