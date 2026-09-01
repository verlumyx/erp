<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Database\Factories\AdjustmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Adjustment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_adjustments';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'AJU';

    public const STATUSES = ['draft', 'pending_approval', 'confirmed', 'completed', 'cancelled'];

    public const TYPES = [
        'physical_count', 'loss', 'damage', 'expiration',
        'theft', 'correction', 'revaluation', 'other',
    ];

    /**
     * Ajuste que no mueve cantidad: reexpresa el costo de lo que ya está en la
     * bodega. Es el único tipo en el que el usuario define el costo unitario.
     */
    public const REVALUATION_TYPE = 'revaluation';

    public const DIRECTIONS = ['in', 'out', 'mixed'];

    /** Alias con el que el kardex reconoce al ajuste como origen. */
    public const MOVEMENT_ORIGIN_TYPE = 'adjustment';

    /**
     * Estados en los que el ajuste ya tocó la existencia. Confirmar es lo que
     * la mueve; anular desde aquí es lo que la devuelve a donde estaba.
     *
     * @var array<int, string>
     */
    public const POSTED_STATUSES = ['confirmed', 'completed'];

    /**
     * Transiciones permitidas. `completed` —el ajuste ya está cerrado— y
     * `cancelled` son terminales.
     *
     * @var array<string, array<int, string>>
     */
    public const STATUS_TRANSITIONS = [
        'draft' => ['pending_approval', 'cancelled'],
        'pending_approval' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'warehouse_id',
        'adjustment_date',
        'type',
        'direction',
        'reason',
        'count_id',
        'total_quantity_in',
        'total_quantity_out',
        'total_cost_in',
        'total_cost_out',
        'net_cost',
        'approved_by',
        'approved_at',
        'cancelled_at',
        'cancellation_reason',
        'attachment_path',
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
            'adjustment_date' => 'date',
            'total_quantity_in' => 'decimal:4',
            'total_quantity_out' => 'decimal:4',
            'total_cost_in' => 'decimal:2',
            'total_cost_out' => 'decimal:2',
            'net_cost' => 'decimal:2',
            'approved_at' => 'datetime',
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

    /** Quién autorizó que el ajuste tocara la existencia. */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AdjustmentLine::class, 'adjustment_id', 'id');
    }

    /** Un ajuste que solo reexpresa costos: ninguna cantidad se mueve. */
    public function isRevaluation(): bool
    {
        return $this->type === self::REVALUATION_TYPE;
    }

    protected static function newFactory(): AdjustmentFactory
    {
        return AdjustmentFactory::new();
    }
}
