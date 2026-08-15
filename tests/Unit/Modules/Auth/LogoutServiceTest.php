<?php

declare(strict_types=1);

use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Modules\Auth\Services\LogoutService;
use App\Modules\User\Models\User;

uses(Tests\TestCase::class);

test('it revokes the current token of the user', function () {
    $user = new User(['email' => 'john@example.com']);

    $repository = Mockery::mock(AuthRepositoryInterface::class);
    $repository->expects('revokeCurrentToken')->with($user);

    $service = new LogoutService($repository);
    $service->execute($user);
});
