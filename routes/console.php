<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Cada noche: marca como vencidas las facturas de venta y de compra que
 * pasaron su fecha con saldo. Nadie las toca al vencer, así que el calendario
 * tiene que venir a buscarlas (`docs/compras.md` §3.2).
 */
Schedule::command('invoices:mark-overdue')->dailyAt('00:30');
