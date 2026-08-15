<?php

declare(strict_types=1);

namespace App\Modules\Menu\Commands;

use App\Modules\Menu\Requests\UpdateMenuRequest;

class UpdateMenuCommand
{
    public function __construct(
        public readonly string $title,
        public readonly string $url,
        public readonly string $permission,
        public readonly string $icon,
    ) {}

    public static function fromRequest(UpdateMenuRequest $request): self
    {
        return new self(
            title: $request->string('title')->toString(),
            url: $request->string('url')->toString(),
            permission: $request->string('permission')->toString(),
            icon: $request->string('icon')->toString(),
        );
    }
}
