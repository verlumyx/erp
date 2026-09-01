<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Commands;

class SearchClientAdvanceCommand
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public readonly array $filters = [],
        public readonly int $limit = 20,
        public readonly int $offset = 0,
        public readonly ?string $companyId = null,
    ) {}
}
