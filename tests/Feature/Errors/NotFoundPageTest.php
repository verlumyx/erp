<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use App\Modules\Entry\Models\Entry;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

/**
 * La pantalla que ve quien abre la dirección de un registro que no existe.
 *
 * Antes era un «Not Found» en texto plano sobre fondo blanco, sin una sola
 * salida. Una dirección tecleada o pegada de un chat no tiene atrás, así que
 * la pantalla tiene que ofrecerlo ella.
 */
test('opening a record that does not exist renders the 404 screen', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('entries.show', [
            'company' => $company->id,
            'id' => (string) Illuminate\Support\Str::uuid7(),
        ]))
        ->assertNotFound()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('errors/404')
                ->has('message')
                /** La salida útil: el listado del módulo del que venía la dirección. */
                ->where('listUrl', route('entries.index', ['company' => $company->id]))
        );
});

test('a record of another company is not found either', function () {
    [$user, $company] = createUserWithCompany();
    [, $other] = createUserWithCompany();

    $entry = Entry::factory()->create(['company_id' => $other->id]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('entries.show', ['company' => $company->id, 'id' => $entry->id]))
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('errors/404'));
});

/**
 * Dentro de la aplicación el enlace muerto no saca al usuario de donde está:
 * vuelve con el aviso, que es lo que ya hacía y sigue siendo lo correcto.
 */
test('the same miss inside the app comes back with a flash instead', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->withHeaders([
            'X-Inertia' => 'true',
            /* Sin la versión que corre, Inertia responde 409 y nunca llega al controlador. */
            'X-Inertia-Version' => (string) (new HandleInertiaRequests)->version(request()),
        ])
        ->get(route('entries.show', [
            'company' => $company->id,
            'id' => (string) Illuminate\Support\Str::uuid7(),
        ]))
        ->assertRedirect()
        ->assertSessionHas('error');
});
