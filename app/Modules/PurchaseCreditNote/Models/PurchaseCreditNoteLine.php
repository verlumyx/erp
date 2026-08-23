<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\PurchaseCreditNoteLineFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseCreditNoteLine extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_purchase_credit_note_lines';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'purchase_credit_note_id',
        'line_number',
        'item_id',
        'measurement_unit_id',
        'purchase_invoice_line_id',
        'warehouse_id',
        'lot_id',
        'quantity',
        'base_quantity',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_id',
        'tax_percent',
        'tax_amount',
        'withholding_percent',
        'withholding_amount',
        'subtotal',
        'total',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'quantity' => 'decimal:4',
            'base_quantity' => 'decimal:4',
            'unit_price' => 'decimal:6',
            'discount_percent' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'tax_percent' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'withholding_percent' => 'decimal:4',
            'withholding_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function purchaseCreditNote(): BelongsTo
    {
        return $this->belongsTo(PurchaseCreditNote::class, 'purchase_credit_note_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'measurement_unit_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    /** Línea de la factura que esta línea acredita. */
    public function purchaseInvoiceLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoiceLine::class, 'purchase_invoice_line_id', 'id');
    }

    protected static function newFactory(): PurchaseCreditNoteLineFactory
    {
        return PurchaseCreditNoteLineFactory::new();
    }
}
