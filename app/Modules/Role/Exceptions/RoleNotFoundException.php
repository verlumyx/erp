<?php

declare(strict_types=1);

namespace App\Modules\Role\Exceptions;

use Exception;

class RoleNotFoundException extends Exception
{
    protected $message = 'Role not found.';
}
