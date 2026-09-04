<?php

declare(strict_types=1);

namespace App\Modules\Import\Models;

use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\ImportFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_imports';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'IMP';

    public const STATUSES = ['draft', 'confirmed', 'completed', 'cancelled'];

    /** Un expediente confirmado ya generó su ajuste: no se edita, se anula. */
    public const EDITABLE_STATUSES = ['draft'];

    /**
     * Estados en los que el expediente ya mandó revalorizar. Confirmar es lo
     * que escribe el ajuste; anular desde aquí es lo que lo retira.
     *
     * @var array<int, string>
     */
    public const SETTLED_STATUSES = ['confirmed', 'completed'];

    /** Cómo se reparte el gasto entre lo que llegó. */
    public const ALLOCATION_METHODS = ['value', 'quantity', 'weight', 'volume'];

    /**
     * Métodos que exigen que el artículo traiga el dato registrado: repartir
     * por un peso que nadie cargó da un costo inventado.
     *
     * @var array<int, string>
     */
    public const DIMENSIONAL_METHODS = ['weight', 'volume'];

    /**
     * Transiciones permitidas. `completed` no se elige desde la pantalla: lo
     * escribe el ajuste al confirmarse, igual que la entrada cierra el
     * traslado.
     *
     * @var array<string, array<int, string>>
     */
    public const STATUS_TRANSITIONS = [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'warehouse_id',
        'import_date',
        'arrival_date',
        'reference',
        'allocation_method',
        'currency',
        'exchange_rate',
        'base_currency',
        'base_exchange_rate',
        'total_charges',
        'total_base_value',
        'total_landed_value',
        'capitalized_amount',
        'variance_amount',
        'adjustment_id',
        'cancelled_at',
        'cancellation_reason',
        'notes',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'import_date' => 'date',
            'arrival_date' => 'date',
            'exchange_rate' => 'decimal:8',
            'base_exchange_rate' => 'decimal:8',
            'total_charges' => 'decimal:2',
            'total_base_value' => 'decimal:2',
            'total_landed_value' => 'decimal:2',
            'capitalized_amount' => 'decimal:2',
            'variance_amount' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    /** El ajuste de revaluación que el expediente generó al confirmarse. */
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(Adjustment::class, 'adjustment_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** Los cobros que se están repartiendo. */
    public function costs(): HasMany
    {
        return $this->hasMany(ImportCost::class, 'import_id', 'id');
    }

    /** Las recepciones que absorben el gasto. */
    public function entries(): HasMany
    {
        return $this->hasMany(ImportEntry::class, 'import_id', 'id');
    }

    /** Los ítems, derivados de las recepciones. */
    public function lines(): HasMany
    {
        return $this->hasMany(ImportLine::class, 'import_id', 'id');
    }

    /** El expediente ya mandó revalorizar: hay un ajuste colgando de él. */
    public function isSettled(): bool
    {
        return in_array($this->status, self::SETTLED_STATUSES, true);
    }

    protected static function newFactory(): ImportFactory
    {
        return ImportFactory::new();
    }
}
