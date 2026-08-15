<?php

declare(strict_types=1);

namespace App\Modules\Lead\Commands;

use App\Modules\Lead\Requests\CreateLeadRequest;
use Illuminate\Support\Str;

class CreateLeadCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
    ) {}

    public static function fromRequest(CreateLeadRequest $request): self
    {
        return new self(
            id: Str::uuid7()->toString(),
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            phone: $request->string('phone')->toString(),
        );
    }
}
