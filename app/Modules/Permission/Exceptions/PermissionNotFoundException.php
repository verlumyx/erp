<?php

declare(strict_types=1);

namespace App\Modules\Permission\Exceptions;

use Exception;

class PermissionNotFoundException extends Exception
{
    protected $message = 'Permission not found.';
}
