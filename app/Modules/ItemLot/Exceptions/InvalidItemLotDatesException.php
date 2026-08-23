<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Exceptions;

use Exception;

class InvalidItemLotDatesException extends Exception
{
    protected $message = 'La fecha de vencimiento no puede ser anterior a la de fabricación.';
}
