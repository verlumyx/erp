<?php

declare(strict_types=1);

namespace App\Modules\Lead\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DemoGetController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('demo');
    }
}
