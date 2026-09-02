<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Models;

use App\Modules\Company\Models\Company;
use App\Modules\ItemSerial\Models\ItemSerial;
use Database\Factories\DispatchLineSerialFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Una de las unidades con serie que salen en una línea del despacho. */
class DispatchLineSerial extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_dispatch_line_serials';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'dispatch_line_id',
        'dispatch_line_lot_id',
        'line_number',
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

    public function dispatchLine(): BelongsTo
    {
        return $this->belongsTo(DispatchLine::class, 'dispatch_line_id', 'id');
    }

    public function dispatchLineLot(): BelongsTo
    {
        return $this->belongsTo(DispatchLineLot::class, 'dispatch_line_lot_id', 'id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(ItemSerial::class, 'serial_id', 'id');
    }

    protected static function newFactory(): DispatchLineSerialFactory
    {
        return DispatchLineSerialFactory::new();
    }
}
