<?php

declare(strict_types=1);

namespace App\Modules\Entry\Models;

use App\Modules\Company\Models\Company;
use App\Modules\ItemSerial\Models\ItemSerial;
use Database\Factories\EntryLineSerialFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una de las unidades con serie que llegan en una línea de la entrada.
 *
 * Igual que el lote, el número es lo que se captura y `serial_id` lo resuelve
 * el sistema al confirmar.
 */
class EntryLineSerial extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_entry_line_serials';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'entry_line_id',
        'entry_line_lot_id',
        'line_number',
        'serial_number',
        'serial_id',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
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

    public function entryLineLot(): BelongsTo
    {
        return $this->belongsTo(EntryLineLot::class, 'entry_line_lot_id', 'id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(ItemSerial::class, 'serial_id', 'id');
    }

    protected static function newFactory(): EntryLineSerialFactory
    {
        return EntryLineSerialFactory::new();
    }
}
