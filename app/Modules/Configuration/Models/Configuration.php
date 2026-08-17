<?php

declare(strict_types=1);

namespace App\Modules\Configuration\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Database\Factories\ConfigurationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Configuración de una empresa. Singleton por empresa: no se lista, no se
 * crea a mano y no se elimina.
 */
class Configuration extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_configurations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'base_currency',
        'secondary_currency',
        'rate_type',
        'allows_rate_override',
        'amount_decimals',
        'price_decimals',
        'created_by',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_decimals' => 'integer',
            'price_decimals' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * La empresa muestra sus importes en dos monedas. Cuando lleva sus cifras
     * en la misma moneda de presentación no hay nada que convertir.
     */
    public function usesDualCurrency(): bool
    {
        return $this->secondary_currency !== null
            && $this->secondary_currency !== $this->base_currency;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    protected static function newFactory(): ConfigurationFactory
    {
        return ConfigurationFactory::new();
    }
}
