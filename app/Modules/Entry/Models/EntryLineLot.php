<?php

declare(strict_types=1);

namespace App\Modules\Entry\Models;

use App\Modules\Company\Models\Company;
use App\Modules\ItemLot\Models\ItemLot;
use Database\Factories\EntryLineLotFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uno de los lotes con los que llega una línea de la entrada.
 *
 * `lot_number` es lo que trae impreso la caja y es lo único que el usuario
 * captura; `lot_id` lo escribe el sistema al confirmar la entrada, cuando ese
 * número ya se buscó —o se dio de alta— en el maestro de lotes.
 */
class EntryLineLot extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_entry_line_lots';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'entry_line_id',
        'line_number',
        'lot_number',
        'lot_id',
        'expires_at',
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
            'expires_at' => 'date',
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

    public function entryLine(): BelongsTo
    {
        return $this->belongsTo(EntryLine::class, 'entry_line_id', 'id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class, 'lot_id', 'id');
    }

    /** Las unidades de este lote que llegaron con serie. */
    public function serials(): HasMany
    {
        return $this->hasMany(EntryLineSerial::class, 'entry_line_lot_id', 'id');
    }

    protected static function newFactory(): EntryLineLotFactory
    {
        return EntryLineLotFactory::new();
    }
}
