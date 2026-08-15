<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Periodo de gracia (días)
    |--------------------------------------------------------------------------
    |
    | Días que una venta permanece renovable tras su vencimiento antes de que
    | sus profiles se liberen. Dentro de la gracia la venta puede renovarse;
    | fuera de ella solo puede reactivarse. Usar config('sales.grace_period_days')
    | en todo el código en lugar de un literal.
    |
    */

    'grace_period_days' => (int) env('SALES_GRACE_PERIOD_DAYS', 3),

];
