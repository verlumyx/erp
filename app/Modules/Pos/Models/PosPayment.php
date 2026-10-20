<?php

declare(strict_types=1);

namespace App\Modules\Pos\Models;

use App\Modules\Company\Models\Company;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosPayment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_pos_payments';

    public $incrementing = false;

    protected $keyType = 'string';

    public const METHODS = [
        'cash',
        'card_debit',
        'card_credit',
        'mobile_payment',
        'transfer',
        'credit_note',
        'other',
    ];

    protected $fillable = [
        'id',
        'company_id',
        'pos_sale_id',
        'pos_session_id',
        'payment_method',
        'currency',
        'exchange_rate',
        'amount_tendered',
        'amount_applied',
        'amount_in_document_currency',
        'reference',
        'card_last_digits',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:8',
            'amount_tendered' => 'decimal:2',
            'amount_applied' => 'decimal:2',
            'amount_in_document_currency' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id', 'id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id', 'id');
    }
}
