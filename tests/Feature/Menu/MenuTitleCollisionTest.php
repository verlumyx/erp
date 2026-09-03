<?php

declare(strict_types=1);

use App\Modules\Menu\Models\Menu;
use Database\Seeders\MenuSeeder;

/**
 * El upsert del seeder busca por `title` + `section`, así que dos entradas con
 * el mismo título en la misma sección son la misma fila: la segunda pisa a la
 * primera y una de las dos desaparece del menú, sin error y sin rastro.
 *
 * Fue lo que pasó cuando la tienda estrenó unos «Ajustes» que se comieron los
 * de Logística. Esta prueba es la red: cualquier título repetido que se cuele
 * en el seeder la revienta.
 */
test('no two menu entries in a section share a title', function () {
    $this->seed(MenuSeeder::class);

    $repeated = Menu::query()
        ->selectRaw('title, section, count(*) as total')
        ->groupBy('title', 'section')
        ->havingRaw('count(*) > 1')
        ->pluck('title')
        ->all();

    expect($repeated)->toBeEmpty();
});

/**
 * El síntoma concreto: los dos «Ajustes» tienen que ser dos filas, cada una
 * bajo su grupo y apuntando a su pantalla.
 */
test('logistics adjustments and store settings are two separate entries', function () {
    $this->seed(MenuSeeder::class);

    /** El modelo solo declara `children()`, así que el padre se busca a mano. */
    $groupOf = fn (?Menu $menu): ?string => $menu === null
        ? null
        : Menu::query()->find($menu->parent_id)?->title;

    $adjustments = Menu::query()->where('url', '/adjustments')->first();
    $storeSettings = Menu::query()->where('url', '/store-settings')->first();

    expect($adjustments)->not->toBeNull()
        ->and($adjustments->title)->toBe('Ajustes')
        ->and($adjustments->permission)->toBe('adjustments.list')
        ->and($groupOf($adjustments))->toBe('Logística');

    expect($storeSettings)->not->toBeNull()
        ->and($storeSettings->title)->toBe('Ajuste de tienda')
        ->and($storeSettings->permission)->toBe('store-settings.edit')
        ->and($groupOf($storeSettings))->toBe('Tienda');
});
