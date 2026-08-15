<?php

declare(strict_types=1);

use App\Modules\Auth\Commands\LoginCommand;
use App\Modules\Auth\Exceptions\InvalidCredentialsException;
use App\Modules\Auth\Exceptions\MaxDevicesReachedException;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Modules\Auth\Services\LoginService;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;

uses(Tests\TestCase::class);

test('it authenticates a user and returns the token', function () {
    $user = new User(['email' => 'john@example.com', 'password' => Hash::make('secret123')]);
    $command = new LoginCommand('john@example.com', 'secret123', 'mobile');

    $repository = Mockery::mock(AuthRepositoryInterface::class);
    $repository->expects('findUserByEmail')->with('john@example.com')->andReturn($user);
    $repository->expects('countTokens')->with($user)->andReturn(0);
    $repository->expects('createToken')->with($user, 'mobile')->andReturn('plain-text-token');

    $service = new LoginService($repository);
    $result = $service->execute($command);

    expect($result['user'])->toBe($user);
    expect($result['token'])->toBe('plain-text-token');
});

test('it throws when the user does not exist', function () {
    $command = new LoginCommand('ghost@example.com', 'secret123', 'mobile');

    $repository = Mockery::mock(AuthRepositoryInterface::class);
    $repository->expects('findUserByEmail')->with('ghost@example.com')->andReturn(null);

    $service = new LoginService($repository);

    expect(fn () => $service->execute($command))->toThrow(InvalidCredentialsException::class);
});

test('it throws when the password is incorrect', function () {
    $user = new User(['email' => 'john@example.com', 'password' => Hash::make('secret123')]);
    $command = new LoginCommand('john@example.com', 'wrong-password', 'mobile');

    $repository = Mockery::mock(AuthRepositoryInterface::class);
    $repository->expects('findUserByEmail')->with('john@example.com')->andReturn($user);

    $service = new LoginService($repository);

    expect(fn () => $service->execute($command))->toThrow(InvalidCredentialsException::class);
});

test('it throws when the device limit is reached', function () {
    $user = new User(['email' => 'john@example.com', 'password' => Hash::make('secret123')]);
    $command = new LoginCommand('john@example.com', 'secret123', 'mobile');

    $repository = Mockery::mock(AuthRepositoryInterface::class);
    $repository->expects('findUserByEmail')->with('john@example.com')->andReturn($user);
    $repository->expects('countTokens')->with($user)->andReturn(LoginService::MAX_DEVICES);
    $repository->shouldNotReceive('createToken');

    $service = new LoginService($repository);

    expect(fn () => $service->execute($command))->toThrow(MaxDevicesReachedException::class);
});
