<?php

declare(strict_types=1);

namespace App\Modules\Category\Exceptions;

use Exception;

class CategoryNotFoundException extends Exception
{
    protected $message = 'Category not found.';
}
