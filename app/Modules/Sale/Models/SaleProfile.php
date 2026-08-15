<?php

declare(strict_types=1);

namespace App\Modules\Sale\Models;

use App\Modules\Account\Models\Profile;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleProfile extends Model
{
    use HasUuids;

    protected $table = 'app_sale_profiles';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * El pivote solo registra el momento de asignación; no maneja updated_at.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'id',
        'sale_id',
        'profile_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profile_id', 'id');
    }
}
