<?php

declare(strict_types=1);

namespace App\Modules\User\Exceptions;

use Exception;

class UserNotFoundException extends Exception
{
    protected $message = 'User not found.';
}
