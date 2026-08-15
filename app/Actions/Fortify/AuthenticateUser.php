<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Modules\Shared\Models\UserCompany;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticateUser
{
    public function __invoke(Request $request): ?User
    {
        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            return null;
        }

        $pivots = UserCompany::with('company')
            ->where('user_id', $user->id)
            ->get();

        if ($user->is_system_owner || $pivots->isEmpty()) {
            return $user;
        }

        $activePivots = $pivots->filter(fn (UserCompany $p): bool => $p->status === 'active');

        if ($activePivots->isEmpty()) {
            throw ValidationException::withMessages([
                'email' => [__('El usuario está inactivo.')],
            ]);
        }

        $fullyActive = $activePivots->filter(fn (UserCompany $p): bool => $p->company?->status === 'active');

        if ($fullyActive->isEmpty()) {
            $message = $pivots->count() === 1
                ? __('La empresa del usuario está inactiva.')
                : __('El usuario o la empresa están inactivos.');

            throw ValidationException::withMessages([
                'email' => [$message],
            ]);
        }

        return $user;
    }
}
