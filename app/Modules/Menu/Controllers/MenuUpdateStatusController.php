<?php

declare(strict_types=1);

namespace App\Modules\Menu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Menu\Requests\UpdateStatusMenuRequest;
use Illuminate\Http\RedirectResponse;

class MenuUpdateStatusController extends Controller
{
    public function __invoke(UpdateStatusMenuRequest $request, string $company, string $id): RedirectResponse
    {
        // Menu items are in-memory; update-status action is not applicable.
        return redirect()->route('menus.show', ['company' => $company, 'id' => $id]);
    }
}
