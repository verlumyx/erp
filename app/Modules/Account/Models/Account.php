<?php

declare(strict_types=1);

namespace App\Modules\Account\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Service\Models\Service;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_accounts';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'ACC';

    /**
     * Statuses válidos de una account.
     */
    public const STATUSES = ['active', 'down', 'maintenance', 'cancelled'];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'service_id',
        'email',
        'password_encrypted',
        'cost',
        'purchase_date',
        'next_renewal',
        'status',
        'notes',
    ];

    /**
     * Nunca exponer la contraseña cifrada en serializaciones por defecto.
     * Solo el endpoint de credentials la devuelve, descifrada y de forma explícita.
     */
    protected $hidden = [
        'password_encrypted',
    ];

    protected function casts(): array
    {
        return [
            // Laravel Crypt: cifra al guardar y descifra al leer (accessor/mutator).
            'password_encrypted' => 'encrypted',
            'cost' => 'decimal:2',
            'purchase_date' => 'date',
            'next_renewal' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class, 'account_id', 'id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(AccountRenewal::class, 'account_id', 'id');
    }

    /**
     * Resumen calculado de los profiles de la account, útil para la UI.
     *
     * @return array{total: int, available: int, occupied: int, maintenance: int}
     */
    public function profilesSummary(): array
    {
        $profiles = $this->profiles;

        return [
            'total' => $profiles->count(),
            'available' => $profiles->where('status', 'available')->count(),
            'occupied' => $profiles->where('status', 'occupied')->count(),
            'maintenance' => $profiles->where('status', 'maintenance')->count(),
        ];
    }

    protected static function newFactory(): AccountFactory
    {
        return AccountFactory::new();
    }
}
