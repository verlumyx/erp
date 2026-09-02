<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Database\Factories\DispatchLineFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DispatchLine extends Model
{
    use HasFactory, HasUuids;

    /** Alias con el que la línea viaja en las columnas `sourceable_type`. */
    public const MORPH_ALIAS = 'dispatch_line';

    protected $table = 'app_dispatch_lines';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'dispatch_id',
        'line_number',
        'item_id',
        'measurement_unit_id',
        'sourceable_type',
        'sourceable_id',
        'location_id',
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
        'delivered_quantity',
        'returned_quantity',
        'unit_cost',
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
            'delivered_quantity' => 'decimal:4',
            'returned_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:6',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class, 'dispatch_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'measurement_unit_id', 'id');
    }

    /** Línea del documento origen que esta línea despacha. */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Los lotes de los que sale la línea. La trazabilidad salió de la línea
     * porque una misma línea puede salir repartida en varios lotes.
     */
    public function lots(): HasMany
    {
        return $this->hasMany(DispatchLineLot::class, 'dispatch_line_id', 'id');
    }

    /** Las unidades con serie que salen en la línea. */
    public function serials(): HasMany
    {
        return $this->hasMany(DispatchLineSerial::class, 'dispatch_line_id', 'id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id', 'id');
    }

    /**
     * Lo que no se quedó el cliente y por tanto vuelve a la bodega. Es la
     * diferencia entre lo que salió y lo que se entregó, nunca negativa.
     */
    public function returnedToWarehouse(): float
    {
        return max(0.0, round((float) $this->quantity - (float) $this->delivered_quantity, 4));
    }

    protected static function newFactory(): DispatchLineFactory
    {
        return DispatchLineFactory::new();
    }
}
