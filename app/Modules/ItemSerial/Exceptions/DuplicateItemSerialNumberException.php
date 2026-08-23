<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Exceptions;

use Exception;

/**
 * Ya existe una serie con ese número para el artículo.
 *
 * La base lo impide con `unique(company_id, item_id, serial_number)`; esto lo
 * detiene antes, para que quien recibe mercancía obtenga un error de dominio y
 * no una excepción de driver.
 */
class DuplicateItemSerialNumberException extends Exception
{
    protected $message = 'Ya existe una serie con ese número para el artículo.';
}
