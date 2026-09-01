<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Models;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\SalesReturnFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_sales_returns';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'DVV';

    public const STATUSES = ['draft', 'confirmed', 'completed', 'cancelled'];

    public const REASONS = [
        'damaged', 'wrong_item', 'expired', 'excess', 'quality', 'client_cancellation', 'other',
    ];

    /**
     * En qué estado vuelve la mercancía. Es lo que decide su destino:
     * `resalable` a la bodega de venta, `damaged` a una de cuarentena y `scrap`
     * a ninguna —se destruye y la pérdida se registra por Ajuste—.
     */
    public const CONDITIONS = ['resalable', 'damaged', 'scrap'];

    /** Condición con la que la mercancía no vuelve a la existencia. */
    public const SCRAP_CONDITION = 'scrap';

    /** Condición que exige una bodega de cuarentena. */
    public const QUARANTINE_CONDITION = 'damaged';

    /** Tipo de bodega al que reingresa lo devuelto en mal estado. */
    public const QUARANTINE_WAREHOUSE_TYPE = 'quarantine';

    /** Alias con el que el kardex reconoce a la devolución como origen. */
    public const MOVEMENT_ORIGIN_TYPE = 'sales_return';

    /**
     * Estados en los que la mercancía ya reingresó a la bodega. Confirmar es lo
     * que la mete; anular desde aquí es lo que la vuelve a sacar.
     *
     * @var array<int, string>
     */
    public const POSTED_STATUSES = ['confirmed', 'completed'];

    /**
     * Transiciones permitidas. `completed` —la devolución ya está acreditada
     * con su nota de crédito— y `cancelled` son terminales.
     *
     * @var array<string, array<int, string>>
     */
    public const STATUS_TRANSITIONS = [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'client_id',
        'sales_invoice_id',
        'dispatch_id',
        'warehouse_id',
        'return_date',
        'reason',
        'reason_detail',
        'condition',
        'currency',
        'exchange_rate',
        'base_currency',
        'base_exchange_rate',
        'subtotal',
        'tax_amount',
        'total',
        'credit_note_id',
        'received_by',
        'cancelled_at',
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
            'return_date' => 'date',
            'exchange_rate' => 'decimal:8',
            'base_exchange_rate' => 'decimal:8',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    /** Factura de origen; vacía en una devolución sin factura previa. */
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    /** Nota de crédito que acredita la devolución, si ya se emitió. */
    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(SalesCreditNote::class, 'credit_note_id', 'id');
    }

    /** Quién recibió físicamente la mercancía en la bodega. */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesReturnLine::class, 'sales_return_id', 'id');
    }

    /**
     * Condición efectiva de una línea: la suya propia si la tiene, y si no la
     * de la cabecera. Es la que decide si esa línea reingresa existencia.
     */
    public function conditionFor(SalesReturnLine $line): string
    {
        return filled($line->condition) ? (string) $line->condition : (string) $this->condition;
    }

    protected static function newFactory(): SalesReturnFactory
    {
        return SalesReturnFactory::new();
    }
}
