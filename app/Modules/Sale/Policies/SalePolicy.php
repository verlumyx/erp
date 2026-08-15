<?php

declare(strict_types=1);

namespace App\Modules\Sale\Policies;

use App\Modules\User\Models\User;

/**
 * Autorización del módulo Ventas (Sales).
 *
 * Las habilidades se delegan al sistema de permisos por rol (hasPermission).
 */
class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('sales.list');
    }

    public function view(User $user): bool
    {
        return $user->hasPermission('sales.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('sales.create');
    }

    public function renew(User $user): bool
    {
        return $user->hasPermission('sales.renew');
    }

    public function reactivate(User $user): bool
    {
        return $user->hasPermission('sales.reactivate');
    }

    public function cancel(User $user): bool
    {
        return $user->hasPermission('sales.cancel');
    }
}
