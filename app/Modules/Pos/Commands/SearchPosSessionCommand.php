<?php

declare(strict_types=1);

namespace App\Modules\Pos\Commands;

class SearchPosSessionCommand
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public readonly string $companyId,
        public readonly array $filters = [],
        public readonly ?string $search = null,
        public readonly string $sortBy = 'opened_at',
        public readonly string $sortDirection = 'desc',
        public readonly int $perPage = 15,
        public readonly int $page = 1,
    ) {}
}
