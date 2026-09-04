<?php

declare(strict_types=1);

namespace App\Modules\Import\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Entry\Models\Entry;
use Database\Factories\ImportEntryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una de las recepciones que el expediente costea.
 *
 * Es el ancla del módulo: la factura dice lo que el proveedor cobró, la entrada
 * dice lo que de verdad llegó. Cuando el proveedor factura 100 y al muelle
 * llegan 90, lo que se revaloriza son 90.
 */
class ImportEntry extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_import_entries';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'import_id',
        'entry_id',
        'line_number',
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

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class, 'import_id', 'id');
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id', 'id');
    }

    protected static function newFactory(): ImportEntryFactory
    {
        return ImportEntryFactory::new();
    }
}
