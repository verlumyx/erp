<?php

declare(strict_types=1);

namespace App\Modules\Menu\Commands;

use App\Modules\Menu\Requests\UpdateStatusMenuRequest;

class UpdateStatusMenuCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusMenuRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
