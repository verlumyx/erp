<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardGetController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('dashboard');
    }
}
