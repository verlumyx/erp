<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;

/**
 * La única respuesta a «¿este artículo lleva existencia?», y con ella la de
 * «¿puede llevar lote?», que resultó ser la misma pregunta.
 *
 * La tenían copiada media docena de servicios como una constante privada, y la
 * pantalla que faltó por enterarse fue la que pedía servicios en una entrada.
 */
test('only services and non inventoried items carry no stock', function (string $type, bool $moves) {
    expect((new Item(['type' => $type]))->movesStock())->toBe($moves);
})->with([
    'inventoried' => ['inventoried', true],
    'serialized' => ['serialized', true],
    'service' => ['service', false],
    'non_inventoried' => ['non_inventoried', false],
]);

test('every type the catalogue admits has an answer', function () {
    foreach (Item::TYPES as $type) {
        expect((new Item(['type' => $type]))->movesStock())->toBeBool();
    }

    expect(Item::NON_STOCKED_TYPES)->each->toBeIn(Item::TYPES);
});

/**
 * `kit` no llegó a significar nada: ninguna regla lo consultaba, la pantalla lo
 * rotulaba «Lotes» y quedaba fuera de los tipos que admitían lote, con lo que
 * elegirlo lograba justo lo contrario de lo que prometía.
 */
test('the catalogue no longer admits the kit type', function () {
    expect(Item::TYPES)->not->toContain('kit');
});
