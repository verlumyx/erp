<?php

declare(strict_types=1);

namespace App\Modules\Import\Models;

use App\Modules\Company\Models\Company;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\Supplier\Models\Supplier;
use Database\Factories\ImportCostFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Uno de los cobros que el expediente reparte.
 *
 * Quien cobra no tiene por qué ser el proveedor de la mercancía, y el importe
 * queda editable siempre: una misma factura puede repartirse entre dos
 * expedientes cuando el embarque llegó en dos viajes, y entonces ninguno de los
 * dos toma el documento completo.
 */
class ImportCost extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_import_costs';

    public $incrementing = false;

    protected $keyType = 'string';

    /** Por qué se cobró. */
    public const CONCEPTS = ['freight', 'insurance', 'customs', 'handling', 'storage', 'other'];

    /** El concepto que obliga a explicarse. */
    public const FREE_CONCEPT = 'other';

    /**
     * Documentos que hoy pueden respaldar un cobro: la factura del
     * transportista, la del agente aduanal o la del propio proveedor.
     *
     * @var array<int, string>
     */
    public const SOURCE_TYPES = [PurchaseInvoice::MORPH_ALIAS];

    protected $fillable = [
        'id',
        'company_id',
        'import_id',
        'line_number',
        'sourceable_type',
        'sourceable_id',
        'supplier_id',
        'concept',
        'description',
        'currency',
        'exchange_rate',
        'amount',
        'converted_amount',
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
            'exchange_rate' => 'decimal:8',
            'amount' => 'decimal:2',
            'converted_amount' => 'decimal:2',
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

    /**
     * Documento que respalda el cobro. No es un FK: es una relación
     * polimórfica, para que mañana admita otros papeles sin una columna por
     * cada uno.
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    protected static function newFactory(): ImportCostFactory
    {
        return ImportCostFactory::new();
    }
}
