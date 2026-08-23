<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Exceptions;

use Exception;

/**
 * Ya existe un lote con ese número para el artículo.
 *
 * La base lo impide con `unique(company_id, item_id, lot_number)`; esto lo
 * detiene antes, para que quien recibe mercancía obtenga un error de dominio y
 * no una excepción de driver.
 */
class DuplicateItemLotNumberException extends Exception
{
    protected $message = 'Ya existe un lote con ese número para el artículo.';
}
