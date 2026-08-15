<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Models;

use Database\Factories\ManualTransactionLineFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualTransactionLine extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_manual_transaction_lines';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'manual_transaction_id',
        'type',
        'category',
        'amount',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function manualTransaction(): BelongsTo
    {
        return $this->belongsTo(ManualTransaction::class, 'manual_transaction_id', 'id');
    }

    protected static function newFactory(): ManualTransactionLineFactory
    {
        return ManualTransactionLineFactory::new();
    }
}
