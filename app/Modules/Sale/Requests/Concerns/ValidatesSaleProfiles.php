<?php

declare(strict_types=1);

namespace App\Modules\Sale\Requests\Concerns;

use App\Modules\Account\Models\Profile;
use App\Modules\Service\Models\Service;
use Illuminate\Validation\Validator;

trait ValidatesSaleProfiles
{
    /**
     * Valida coherencia de servicio, disponibilidad y capacidad de los profiles
     * elegidos respecto al snapshot de la venta (service + capacity).
     *
     * @param  array<int, string>  $profileIds
     */
    protected function validateProfilesCoherence(
        Validator $validator,
        ?string $companyId,
        string $serviceId,
        string $capacity,
        array $profileIds,
    ): void {
        $maxProfiles = (int) Service::query()
            ->where('id', $serviceId)
            ->where('company_id', $companyId)
            ->value('max_profiles');

        /** @var \Illuminate\Support\Collection<int, Profile> $profiles */
        $profiles = Profile::query()
            ->with('account:id,company_id,service_id')
            ->whereIn('id', $profileIds)
            ->get();

        if ($profiles->count() !== count(array_unique($profileIds))) {
            $validator->errors()->add('profile_ids', 'Uno o más profiles no existen.');

            return;
        }

        foreach ($profiles as $profile) {
            $account = $profile->account;

            if ($account === null || $account->company_id !== $companyId || $account->service_id !== $serviceId) {
                $validator->errors()->add('profile_ids', 'Todos los profiles deben pertenecer a cuentas del servicio del plan seleccionado.');

                break;
            }
        }

        foreach ($profiles as $profile) {
            if ($profile->status !== 'available') {
                $validator->errors()->add('profile_ids', 'Todos los profiles seleccionados deben estar disponibles.');

                break;
            }
        }

        if ($capacity === 'profile' && $profiles->count() !== 1) {
            $validator->errors()->add('profile_ids', 'Un plan de capacidad "profile" requiere exactamente 1 profile.');
        }

        if ($capacity === 'full_account') {
            if ($profiles->count() !== $maxProfiles) {
                $validator->errors()->add('profile_ids', "Un plan de cuenta completa requiere exactamente {$maxProfiles} profiles.");
            }

            if ($profiles->pluck('account_id')->unique()->count() > 1) {
                $validator->errors()->add('profile_ids', 'Todos los profiles de una cuenta completa deben pertenecer a la misma cuenta.');
            }
        }
    }
}
