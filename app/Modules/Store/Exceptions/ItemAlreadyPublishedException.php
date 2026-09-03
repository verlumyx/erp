<?php

declare(strict_types=1);

namespace App\Modules\Store\Exceptions;

use Exception;

/** Un artículo tiene como máximo una publicación por empresa. */
class ItemAlreadyPublishedException extends Exception
{
    protected $message = 'El artículo ya tiene una publicación en la tienda.';
}
