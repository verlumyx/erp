<?php

declare(strict_types=1);

namespace App\Modules\Pos\Commands;

class SearchPosRegisterCommand
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public readonly string $companyId,
        public readonly array $filters = [],
        public readonly ?string $search = null,
        public readonly string $sortBy = 'name',
        public readonly string $sortDirection = 'asc',
        public readonly int $perPage = 10,
        public readonly int $page = 1,
    ) {}
}
