<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Policies;

use App\Modules\User\Models\User;

/**
 * Autorización del módulo Transacciones manuales (ManualTransaction).
 *
 * Las habilidades se delegan al sistema de permisos por rol (hasPermission).
 */
class ManualTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manual-transactions.list');
    }

    public function view(User $user): bool
    {
        return $user->hasPermission('manual-transactions.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manual-transactions.create');
    }

    public function approve(User $user): bool
    {
        return $user->hasPermission('manual-transactions.approve');
    }

    public function cancel(User $user): bool
    {
        return $user->hasPermission('manual-transactions.cancel');
    }
}
