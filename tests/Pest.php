<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->beforeEach(function () {
        $this->withoutVite();
        $this->withoutMiddleware(Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        // El catálogo de monedas es global y lo valida cualquier campo `currency`.
        $this->seed(Database\Seeders\CurrencySeeder::class);
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create an authenticated-ready user that belongs to a freshly created company.
 *
 * The user is granted a role with permission_type "all", so by default it has
 * full access. Tests that need a restricted user should grant explicit
 * permissions instead (see assignRoleWithPermissions).
 *
 * @return array{0: \App\Modules\User\Models\User, 1: \App\Modules\Company\Models\Company}
 */
function createUserWithCompany(): array
{
    $user = \App\Modules\User\Models\User::factory()->create();

    $company = \App\Modules\Company\Models\Company::create([
        'name' => 'Company '.uniqid(),
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $role = \App\Modules\Role\Models\Role::create([
        'company_id' => $company->id,
        'name' => 'Acceso Total '.uniqid(),
        'status' => 'active',
        'permission_type' => 'all',
    ]);

    $user->companies()->attach($company->id, [
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'role_id' => $role->id,
        'status' => 'active',
    ]);

    /** Ninguna empresa existe sin configuración: la fixture refleja lo mismo. */
    \App\Modules\Configuration\Models\Configuration::create([
        'company_id' => $company->id,
        'base_currency' => 'USD',
        'secondary_currency' => \App\Modules\Currency\Models\Currency::LOCAL_CODE,
        'rate_type' => 'legal',
        'allows_rate_override' => 'yes',
        'amount_decimals' => 2,
        'price_decimals' => 6,
        'created_by' => $user->id,
    ]);

    return [$user, $company];
}

/**
 * Assign a custom role to the user within the given company, granting exactly
 * the provided permission action strings (permission_type "custom").
 *
 * @param  array<int, string>  $permissions
 */
function assignRoleWithPermissions(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    array $permissions = [],
): \App\Modules\Role\Models\Role {
    $role = \App\Modules\Role\Models\Role::create([
        'company_id' => $company->id,
        'name' => 'Custom '.uniqid(),
        'status' => 'active',
        'permission_type' => 'custom',
    ]);

    foreach ($permissions as $permission) {
        \App\Modules\Role\Models\RolePermission::create([
            'role_id' => $role->id,
            'permission' => $permission,
        ]);
    }

    \App\Modules\Shared\Models\UserCompany::where('user_id', $user->id)
        ->where('company_id', $company->id)
        ->update(['role_id' => $role->id]);

    return $role;
}

/**
 * User + company + one measurement unit of that company, the minimum needed to
 * create an item (every item requires at least a base unit).
 *
 * @return array{0: \App\Modules\User\Models\User, 1: \App\Modules\Company\Models\Company, 2: \App\Modules\MeasurementUnit\Models\MeasurementUnit}
 */
function itemScenario(): array
{
    [$user, $company] = createUserWithCompany();

    $unit = \App\Modules\MeasurementUnit\Models\MeasurementUnit::factory()
        ->create(['company_id' => $company->id]);

    return [$user, $company, $unit];
}

/**
 * A valid `suppliers.store` / `suppliers.update` payload, overridable per test.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function supplierPayload(array $overrides = []): array
{
    return [
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'name' => 'Distribuidora Andina C.A.',
        'document_type' => 'J',
        'document_number' => '123456789',
        'currency' => 'USD',
        ...$overrides,
    ];
}

/**
 * A valid `clients.store` / `clients.update` payload, overridable per test.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function clientPayload(array $overrides = []): array
{
    return [
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'name' => 'Camila Rojas',
        'document_type' => 'V',
        'document_number' => '12345678',
        'credit_blocked' => 'no',
        ...$overrides,
    ];
}

/**
 * User + company + the minimum masters a purchase order needs: an active
 * supplier, a warehouse and a purchasable item with its base unit.
 *
 * @return array{
 *     0: \App\Modules\User\Models\User,
 *     1: \App\Modules\Company\Models\Company,
 *     2: \App\Modules\Supplier\Models\Supplier,
 *     3: \App\Modules\Warehouse\Models\Warehouse,
 *     4: \App\Modules\Item\Models\Item,
 *     5: \App\Modules\MeasurementUnit\Models\MeasurementUnit
 * }
 */
function purchaseOrderScenario(): array
{
    [$user, $company] = createUserWithCompany();

    $supplier = \App\Modules\Supplier\Models\Supplier::factory()->create(['company_id' => $company->id]);
    $warehouse = \App\Modules\Warehouse\Models\Warehouse::factory()->create(['company_id' => $company->id]);
    $unit = \App\Modules\MeasurementUnit\Models\MeasurementUnit::factory()->create(['company_id' => $company->id]);
    $item = \App\Modules\Item\Models\Item::factory()->create([
        'company_id' => $company->id,
        'is_purchasable' => 'yes',
    ]);

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);

    return [$user, $company, $supplier, $warehouse, $item, $unit];
}

/**
 * A valid `purchase-orders.store` / `purchase-orders.update` payload.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function purchaseOrderPayload(
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): array {
    return [
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => now()->toDateString(),
        'currency' => 'USD',
        'exchange_rate' => 1,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 25,
            ],
        ],
        ...$overrides,
    ];
}

/**
 * User + company + the minimum masters a sales order needs: a client, a
 * warehouse and one sellable item with its base unit.
 *
 * @return array{
 *     0: \App\Modules\User\Models\User,
 *     1: \App\Modules\Company\Models\Company,
 *     2: \App\Modules\Client\Models\Client,
 *     3: \App\Modules\Warehouse\Models\Warehouse,
 *     4: \App\Modules\Item\Models\Item,
 *     5: \App\Modules\MeasurementUnit\Models\MeasurementUnit
 * }
 */
function salesOrderScenario(): array
{
    [$user, $company] = createUserWithCompany();

    $client = \App\Modules\Client\Models\Client::factory()
        ->create(['company_id' => $company->id]);

    $warehouse = \App\Modules\Warehouse\Models\Warehouse::factory()
        ->create(['company_id' => $company->id]);

    $unit = \App\Modules\MeasurementUnit\Models\MeasurementUnit::factory()
        ->create(['company_id' => $company->id]);

    $item = \App\Modules\Item\Models\Item::factory()
        ->create(['company_id' => $company->id]);

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);

    return [$user, $company, $client, $warehouse, $item, $unit];
}

/**
 * A valid `sales-orders.store` / `sales-orders.update` payload, overridable
 * per test. Without explicit lines it carries one line of the given item.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function salesOrderPayload(
    \App\Modules\Client\Models\Client $client,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): array {
    return [
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => now()->toDateString(),
        'currency' => 'USD',
        'exchange_rate' => 1,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 2,
                'unit_price' => 100,
            ],
        ],
        ...$overrides,
    ];
}

/**
 * Creates a sales order over HTTP and returns the freshly saved model, so the
 * tests that need an existing order do not rebuild the payload each time.
 *
 * @param  array<string, mixed>  $overrides
 */
function createSalesOrder(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\SalesOrder\Models\SalesOrder {
    $payload = salesOrderPayload($client, $warehouse, $item, $unit, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\SalesOrder\Models\SalesOrder::with('lines')->findOrFail($payload['id']);
}

/**
 * A valid `items.store` / `items.update` payload, overridable per test.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function itemPayload(
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): array {
    return [
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'sku' => 'SKU-001',
        'name' => 'Martillo de carpintero',
        'type' => 'inventoried',
        'cost_method' => 'average',
        'is_purchasable' => 'yes',
        'is_sellable' => 'yes',
        'units' => [
            ['measurement_unit_id' => $unit->id, 'is_base' => 'yes', 'conversion_factor' => 1],
        ],
        ...$overrides,
    ];
}
