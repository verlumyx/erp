<?php

declare(strict_types=1);

namespace App\Modules\Account\Policies;

use App\Modules\User\Models\User;

/**
 * Autorización del módulo Inventario (Accounts).
 *
 * Las habilidades se delegan al sistema de permisos por rol (hasPermission),
 * de modo que admin/supervisor (roles con los permisos accounts.*) tienen acceso
 * completo y agente (sin esos permisos) queda excluido del módulo.
 */
class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('accounts.list');
    }

    public function view(User $user): bool
    {
        return $user->hasPermission('accounts.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('accounts.create');
    }

    public function update(User $user): bool
    {
        return $user->hasPermission('accounts.update');
    }

    public function renew(User $user): bool
    {
        return $user->hasPermission('accounts.renew');
    }

    /**
     * Ver credentials descifradas: solo admin/supervisor (permiso accounts.credentials).
     */
    public function viewCredentials(User $user): bool
    {
        return $user->hasPermission('accounts.credentials');
    }
}
