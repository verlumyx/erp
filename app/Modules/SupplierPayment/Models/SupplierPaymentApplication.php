<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Models;

use App\Modules\Company\Models\Company;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\User\Models\User;
use Database\Factories\SupplierPaymentApplicationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una fila del reparto: qué documento abona qué factura y por cuánto.
 *
 * Vive en el módulo de pagos porque es él quien la estrena, pero la tabla la
 * comparten los tres orígenes posibles (`payment`, `advance`, `credit_note`).
 */
class SupplierPaymentApplication extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_supplier_payment_applications';

    public $incrementing = false;

    protected $keyType = 'string';

    public const SOURCE_TYPES = ['payment', 'advance', 'credit_note'];

    public const STATUSES = ['active', 'reversed'];

    protected $fillable = [
        'id',
        'company_id',
        'purchase_invoice_id',
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

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    protected static function newFactory(): SupplierPaymentApplicationFactory
    {
        return SupplierPaymentApplicationFactory::new();
    }
}
