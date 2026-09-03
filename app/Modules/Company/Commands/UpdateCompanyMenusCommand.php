<?php

declare(strict_types=1);

namespace App\Modules\Company\Commands;

use App\Modules\Company\Requests\UpdateCompanyMenusRequest;

class UpdateCompanyMenusCommand
{
    /** @param  string[]  $disabledMenus */
    public function __construct(
        public readonly array $disabledMenus = [],
    ) {}

    public static function fromRequest(UpdateCompanyMenusRequest $request): self
    {
        /** @var string[] $disabledMenus */
        $disabledMenus = $request->input('disabled_menus', []);

        return new self(
            disabledMenus: array_values($disabledMenus),
        );
    }
}
