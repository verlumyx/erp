<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Modules\Shared\Models\UserCompany;
use App\Modules\User\Commands\UpdateStatusUserCommand;
use App\Modules\User\Exceptions\UserNotFoundException;

class UserUpdateStatusService
{
    public function execute(string $userId, string $companyId, UpdateStatusUserCommand $command): void
    {
        $userCompany = UserCompany::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->first();

        if ($userCompany === null) {
            throw new UserNotFoundException;
        }

        $userCompany->update(['status' => $command->status]);
    }
}
