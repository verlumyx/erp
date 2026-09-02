<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Models;

use App\Modules\Company\Models\Company;
use App\Modules\ItemLot\Models\ItemLot;
use Database\Factories\DispatchLineLotFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uno de los lotes de los que sale una línea del despacho.
 *
 * El despacho consume trazabilidad que ya existe, así que aquí el lote se
 * elige del maestro y `lot_id` nunca es nulo: lo contrario del lote de una
 * entrada, que nace con el documento.
 */
class DispatchLineLot extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_dispatch_line_lots';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'dispatch_line_id',
        'line_number',
        'lot_id',
        'quantity',
        'base_quantity',
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function dispatchLine(): BelongsTo
    {
        return $this->belongsTo(DispatchLine::class, 'dispatch_line_id', 'id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class, 'lot_id', 'id');
    }

    /** Las unidades de este lote que salen identificadas por su serie. */
    public function serials(): HasMany
    {
        return $this->hasMany(DispatchLineSerial::class, 'dispatch_line_lot_id', 'id');
    }

    protected static function newFactory(): DispatchLineLotFactory
    {
        return DispatchLineLotFactory::new();
    }
}
