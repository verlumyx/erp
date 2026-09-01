<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Models;

use App\Modules\Company\Models\Company;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\User\Models\User;
use Database\Factories\ClientCollectionApplicationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una fila del reparto: qué documento abona qué factura de venta y por cuánto.
 *
 * Vive en el módulo de cobros porque es él quien la estrena, pero la tabla la
 * comparten los tres orígenes posibles (`collection`, `advance`,
 * `credit_note`).
 */
class ClientCollectionApplication extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_client_collection_applications';

    public $incrementing = false;

    protected $keyType = 'string';

    public const SOURCE_TYPES = ['collection', 'advance', 'credit_note'];

    public const STATUSES = ['active', 'reversed'];

    protected $fillable = [
        'id',
        'company_id',
        'sales_invoice_id',
        'source_type',
        'source_id',
        'applied_amount',
        'applied_at',
        'exchange_rate',
        'exchange_difference',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'applied_amount' => 'decimal:2',
            'applied_at' => 'datetime',
            'exchange_rate' => 'decimal:8',
            'exchange_difference' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    protected static function newFactory(): ClientCollectionApplicationFactory
    {
        return ClientCollectionApplicationFactory::new();
    }
}
