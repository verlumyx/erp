<?php

declare(strict_types=1);

namespace App\Modules\Menu\Commands;

class SearchMenuCommand
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public readonly array $filters = [],
        public readonly int $limit = 100,
        public readonly int $offset = 0,
    ) {}
}
