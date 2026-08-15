<?php

declare(strict_types=1);

namespace App\Modules\User\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Role\Models\Role;
use App\Modules\Shared\Models\UserCompany;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, TwoFactorAuthenticatable;

    protected $table = 'users';

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'is_active',
        'is_system_owner',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_active' => 'boolean',
            'is_system_owner' => 'boolean',
        ];
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'user_company', 'user_id', 'company_id')
            ->withPivot(['role_id', 'status', 'is_default'])
            ->withTimestamps();
    }

    public function hasPermission(string $permission): bool
    {
        $role = $this->getCurrentCompanyRole();

        if (! $role) {
            return false;
        }

        if ($role->permission_type === 'all') {
            return true;
        }

        return $role->permissions()
            ->where('permission', $permission)
            ->exists();
    }

    /**
     * @return array<string>
     */
    public function getPermissions(): array
    {
        $role = $this->getCurrentCompanyRole();

        if (! $role) {
            return [];
        }

        if ($role->permission_type === 'all') {
            $permissionRepository = app(\App\Modules\Permission\Repositories\Contracts\PermissionRepositoryInterface::class);

            return $permissionRepository->getAllPermissionsFlat();
        }

        return $role->permissions()->pluck('permission')->toArray();
    }

    private function getCurrentCompanyRole(): ?Role
    {
        $companyId = session('current_company_id');

        if (! $companyId) {
            return null;
        }

        $userCompany = UserCompany::where('user_id', $this->id)
            ->where('company_id', $companyId)
            ->with('role.permissions')
            ->first();

        return $userCompany?->role;
    }
}
