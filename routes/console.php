<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cada noche: expira las ventas vencidas y libera los profiles fuera de gracia.
Schedule::command('sales:expire')->dailyAt('00:30');
