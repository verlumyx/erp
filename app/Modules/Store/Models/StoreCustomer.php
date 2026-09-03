<?php

declare(strict_types=1);

namespace App\Modules\Store\Models;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Database\Factories\StoreCustomerFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Comprador de la tienda: la cuenta con la que alguien compra. No es un
 * cliente del ERP, es quien puede llegar a serlo; el vínculo con
 * `app_clients` vive en `client_id` y se hace una sola vez.
 *
 * Es `Authenticatable` porque inicia sesión en la tienda con token Sanctum.
 * Los tokens son polimórficos y el mapa de morphs no es `enforce`, así que
 * el FQCN funciona sin registrarlo.
 */
class StoreCustomer extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $table = 'app_store_customers';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CODE_PREFIX = 'CWE';

    /** Habilidad del token Sanctum con la que la tienda autentica al comprador. */
    public const TOKEN_ABILITY = 'store-customer';

    public const STATUSES = ['invited', 'active', 'inactive'];

    public const LINK_SOURCES = ['rif', 'invitation', 'conversion', 'manual'];

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'client_id',
        'name',
        'email',
        'phone',
        'document_type',
        'document_number',
        'password_hash',
        'email_verified_at',
        'last_login_at',
        'linked_at',
        'linked_by',
        'link_source',
        'invitation_token_hash',
        'invitation_expires_at',
        'status',
        'created_by',
    ];

    protected $hidden = [
        'password_hash',
        'invitation_token_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'linked_at' => 'datetime',
            'invitation_expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** La contraseña vive en `password_hash`, no en `password`. */
    public function getAuthPassword(): ?string
    {
        return $this->password_hash;
    }

    public function isLinked(): bool
    {
        return $this->client_id !== null;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function linker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'store_customer_id', 'id');
    }

    protected static function newFactory(): StoreCustomerFactory
    {
        return StoreCustomerFactory::new();
    }
}
