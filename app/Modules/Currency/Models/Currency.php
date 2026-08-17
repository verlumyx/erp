<?php

declare(strict_types=1);

namespace App\Modules\Currency\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Moneda del catálogo global. No pertenece a ninguna empresa: la misma lista
 * alimenta todos los campos `currency` del sistema.
 */
class Currency extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_currencies';

    /**
     * Moneda local. Toda tasa de cambio se expresa como "cuántos bolívares
     * vale 1 unidad de la moneda extranjera", así que esta moneda nunca tiene
     * tasa propia: su equivalencia es siempre 1.
     */
    public const LOCAL_CODE = 'VES';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'code',
        'name',
        'symbol',
        'order',
        'status',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): CurrencyFactory
    {
        return CurrencyFactory::new();
    }
}
