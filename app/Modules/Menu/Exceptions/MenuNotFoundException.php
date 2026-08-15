<?php

declare(strict_types=1);

namespace App\Modules\Menu\Exceptions;

use Exception;

class MenuNotFoundException extends Exception
{
    protected $message = 'Menu item not found.';
}
