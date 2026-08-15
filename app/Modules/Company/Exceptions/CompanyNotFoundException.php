<?php

declare(strict_types=1);

namespace App\Modules\Company\Exceptions;

use Exception;

class CompanyNotFoundException extends Exception
{
    protected $message = 'Company not found.';
}
