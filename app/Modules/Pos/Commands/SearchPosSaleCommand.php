<?php

declare(strict_types=1);

namespace App\Modules\Pos\Commands;

class SearchPosSaleCommand
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public readonly string $companyId,
        public readonly array $filters = [],
        public readonly ?string $search = null,
        public readonly string $sortBy = 'sale_date',
        public readonly string $sortDirection = 'desc',
        public readonly int $perPage = 10,
        public readonly int $page = 1,
    ) {}
}
