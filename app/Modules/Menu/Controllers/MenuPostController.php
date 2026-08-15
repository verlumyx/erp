<?php

declare(strict_types=1);

namespace App\Modules\Menu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Menu\Requests\CreateMenuRequest;
use Illuminate\Http\RedirectResponse;

class MenuPostController extends Controller
{
    public function __invoke(CreateMenuRequest $request): RedirectResponse
    {
        // Menu items are in-memory; store action is not applicable.
        return redirect()->route('menus.index', ['company' => $request->route('company')]);
    }
}
