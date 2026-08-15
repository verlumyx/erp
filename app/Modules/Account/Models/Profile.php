<?php

declare(strict_types=1);

namespace App\Modules\Account\Models;

use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_profiles';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Statuses válidos de un profile.
     */
    public const STATUSES = ['available', 'occupied', 'maintenance'];

    /**
     * Transiciones de status permitidas (origen => destinos válidos).
     *
     * @var array<string, array<int, string>>
     */
    public const TRANSICIONES = [
        'available' => ['occupied', 'maintenance'],
        'occupied' => ['available', 'maintenance'],
        'maintenance' => ['available', 'occupied'],
    ];

    protected $fillable = [
        'id',
        'account_id',
        'number',
        'pin',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    /**
     * Indica si un profile puede transicionar de un status a otro.
     * Mantener el mismo status siempre es válido (no-op).
     */
    public static function canTransition(string $desde, string $hacia): bool
    {
        if ($desde === $hacia) {
            return true;
        }

        return in_array($hacia, self::TRANSICIONES[$desde] ?? [], true);
    }

    protected static function newFactory(): ProfileFactory
    {
        return ProfileFactory::new();
    }
}
