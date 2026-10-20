<?php

declare(strict_types=1);

namespace App\Modules\Pos\Models;

use App\Modules\Company\Models\Company;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSessionDenomination extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_pos_session_denominations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'pos_session_id',
        'currency',
        'denomination',
        'count',
        'total_amount',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'denomination' => 'decimal:2',
            'count' => 'integer',
            'total_amount' => 'decimal:2',
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
}
