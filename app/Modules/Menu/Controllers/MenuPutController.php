<?php

declare(strict_types=1);

namespace App\Modules\Menu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Menu\Requests\UpdateMenuRequest;
use Illuminate\Http\RedirectResponse;

class MenuPutController extends Controller
{
    public function __invoke(UpdateMenuRequest $request, string $company, string $id): RedirectResponse
    {
        // Menu items are in-memory; update action is not applicable.
        return redirect()->route('menus.show', ['company' => $company, 'id' => $id]);
    }
}
