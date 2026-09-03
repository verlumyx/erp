<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Database\Factories\InventoryMovementFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Kardex: una fila por cada afectación de existencia.
 *
 * Es **inmutable** — un error se corrige con un movimiento de contrapartida
 * (`reversal_of_id`), nunca editando el original. Lo escribe únicamente
 * `InventoryMovementRegisterService`, en la misma transacción que actualiza
 * `app_item_stocks`.
 */
class InventoryMovement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_inventory_movements';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'MOV';

    /** @var array<int, string> */
    public const TYPES = [
        'in',
        'out',
        'transfer_in',
        'transfer_out',
        'adjustment_in',
        'adjustment_out',
    ];

    /**
     * Tipos que cargan existencia. El resto la descarga: el signo del
     * movimiento sale de aquí, nunca de la cantidad, que siempre es positiva.
     *
     * @var array<int, string>
     */
    public const INBOUND_TYPES = ['in', 'transfer_in', 'adjustment_in'];

    /**
     * Contrapartida de cada tipo, usada al anular el documento origen.
     *
     * @var array<string, string>
     */
    public const OPPOSITE_TYPES = [
        'in' => 'out',
        'out' => 'in',
        'transfer_in' => 'transfer_out',
        'transfer_out' => 'transfer_in',
        'adjustment_in' => 'adjustment_out',
        'adjustment_out' => 'adjustment_in',
    ];

    /**
     * Documentos que pueden originar un movimiento.
     *
     * Son tres y solo tres: la Entrada mete la mercancía, el Despacho la saca y
     * el Ajuste cuadra lo que el conteo desmiente. Un documento comercial
     * —factura, nota de crédito, devolución, traslado— describe un acuerdo con
     * un tercero, no un hecho físico, y nunca escribe aquí.
     *
     * @var array<int, string>
     */
    public const ORIGIN_TYPES = [
        'entry',
        'dispatch',
        'adjustment',
    ];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'movement_date',
        'type',
        'origin_type',
        'origin_id',
        'origin_line_id',
        'item_id',
        'warehouse_id',
        'location_id',
        'lot_id',
        'serial_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'balance_quantity',
        'balance_cost',
        'balance_value',
        'reversal_of_id',
        'status',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'movement_date' => 'datetime',
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:6',
            'total_cost' => 'decimal:2',
            'balance_quantity' => 'decimal:4',
            'balance_cost' => 'decimal:6',
            'balance_value' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id', 'id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class, 'lot_id', 'id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(ItemSerial::class, 'serial_id', 'id');
    }

    /** Movimiento original que esta fila revierte. */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id', 'id');
    }

    /** Contrapartida que anuló este movimiento, si ya se emitió. */
    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    protected static function newFactory(): InventoryMovementFactory
    {
        return InventoryMovementFactory::new();
    }
}
