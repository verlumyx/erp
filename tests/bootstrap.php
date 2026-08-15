<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Force an isolated testing environment
|--------------------------------------------------------------------------
|
| The Docker container exports real environment variables (APP_ENV=local,
| DB_CONNECTION=pgsql, DB_DATABASE=erp_dev). PHPUnit's <env> entries do
| NOT override variables that already exist in the process, so without this
| guard the test suite would connect to the development database and
| RefreshDatabase would wipe it.
|
| putenv() runs here, in-process, before Laravel reads any configuration, so
| these values win regardless of what the container exported.
|
*/

foreach ([
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
] as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../vendor/autoload.php';
