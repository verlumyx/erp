<?php

declare(strict_types=1);

namespace App\Modules\Store\Commands;

/**
 * Una foto lista para guardarse en `app_store_item_images`: la ruta donde
 * quedó el archivo y sus dimensiones, ya resueltas por el servicio.
 */
class StoreItemImageData
{
    public function __construct(
        public readonly string $path,
        public readonly ?int $width,
        public readonly ?int $height,
        public readonly ?string $altText = null,
    ) {}
}
