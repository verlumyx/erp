<?php

declare(strict_types=1);

namespace App\Modules\Pos\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\Tax\Models\Tax;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSaleLine extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_pos_sale_lines';

    public $incrementing = false;

    protected $keyType = 'string';

    public const MORPH_ALIAS = 'pos_sale_line';

    protected $fillable = [
        'id',
        'company_id',
        'pos_sale_id',
        'line_number',
        'item_id',
        'measurement_unit_id',
        'quantity',
        'base_quantity',
        'unit_price',
        'unit_cost',
        'discount_percent',
        'discount_amount',
        'tax_id',
        'tax_percent',
        'tax_amount',
        'subtotal',
        'total',
        'status',
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
            'unit_cost' => 'decimal:6',
            'discount_percent' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'tax_percent' => 'decimal:4',
            'tax_amount' => 'decimal:2',
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'measurement_unit_id', 'id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id', 'id');
    }
}
