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

    todayExchangeRate($company, $user);

    return [$user, $company, $supplier, $warehouse, $item, $unit];
}

/**
 * Tasa del día para la moneda por defecto de la empresa. Sin ella ningún
 * documento se emite: el resolver bloquea la emisión antes que inventar una.
 */
function todayExchangeRate(
    \App\Modules\Company\Models\Company $company,
    \App\Modules\User\Models\User $user,
    string $currency = 'USD',
    float $rate = 36.5,
): \App\Modules\ExchangeRate\Models\ExchangeRate {
    return \App\Modules\ExchangeRate\Models\ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => $currency,
        'rate_date' => now()->toDateString(),
        'rate' => $rate,
        'type' => 'legal',
        'status' => 'active',
        'created_by' => $user->id,
    ]);
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

    todayExchangeRate($company, $user);

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
 * User + company + the minimum masters a sales invoice needs. It is the same
 * scenario as a sales order: a client, a warehouse and one sellable item with
 * its base unit.
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
function salesInvoiceScenario(): array
{
    return salesOrderScenario();
}

/**
 * A valid `sales-invoices.store` / `sales-invoices.update` payload, overridable
 * per test. Without explicit lines it carries one line of the given item.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function salesInvoicePayload(
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
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->toDateString(),
        'currency' => 'USD',
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
 * Creates a sales invoice over HTTP and returns the freshly saved model, so
 * the tests that need an existing invoice do not rebuild the payload each time.
 *
 * @param  array<string, mixed>  $overrides
 */
function createSalesInvoice(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\SalesInvoice\Models\SalesInvoice {
    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\SalesInvoice\Models\SalesInvoice::with('lines')->findOrFail($payload['id']);
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

/**
 * User + company + the minimum masters a purchase invoice needs. It is the
 * same scenario as a purchase order: a supplier, a warehouse and a purchasable
 * item with its base unit.
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
function purchaseInvoiceScenario(): array
{
    return purchaseOrderScenario();
}

/**
 * A valid `purchase-invoices.store` / `purchase-invoices.update` payload.
 *
 * `due_date` is left out on purpose: without it the backend derives it from the
 * supplier's credit days, which is the normal path.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function purchaseInvoicePayload(
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
        'supplier_invoice_number' => '00-'.fake()->unique()->numerify('######'),
        'invoice_date' => now()->toDateString(),
        'currency' => 'USD',
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
 * Creates a purchase order over HTTP and returns the freshly saved model, so
 * the tests that need a source document do not rebuild the payload each time.
 *
 * It is not called `createPurchaseOrder`: that name is already taken by a
 * helper local to the purchase order update test, and Pest loads every test
 * file into the same global scope.
 *
 * @param  array<string, mixed>  $overrides
 */
function sourcePurchaseOrder(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\PurchaseOrder\Models\PurchaseOrder {
    $payload = purchaseOrderPayload($supplier, $warehouse, $item, $unit, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\PurchaseOrder\Models\PurchaseOrder::with('lines')->findOrFail($payload['id']);
}

/**
 * Creates a purchase invoice over HTTP and returns the freshly saved model.
 *
 * @param  array<string, mixed>  $overrides
 */
function createPurchaseInvoice(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\PurchaseInvoice\Models\PurchaseInvoice {
    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\PurchaseInvoice\Models\PurchaseInvoice::with('lines')->findOrFail($payload['id']);
}

/**
 * User + company + the minimum masters a purchase credit note needs. It is the
 * same scenario as a purchase invoice: a supplier, a warehouse and a
 * purchasable item with its base unit.
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
function purchaseCreditNoteScenario(): array
{
    return purchaseOrderScenario();
}

/**
 * A valid `purchase-credit-notes.store` / `purchase-credit-notes.update`
 * payload.
 *
 * `purchase_invoice_id` is left out on purpose: the plain case is a note that
 * does not correct a particular invoice. The tests that need one pass it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function purchaseCreditNotePayload(
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): array {
    return [
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'supplier_id' => $supplier->id,
        'note_date' => now()->toDateString(),
        'reason' => 'return',
        'currency' => 'USD',
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 2,
                'unit_price' => 25,
            ],
        ],
        ...$overrides,
    ];
}

/**
 * Creates a purchase credit note over HTTP and returns the freshly saved model.
 *
 * @param  array<string, mixed>  $overrides
 */
function createPurchaseCreditNote(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote {
    $payload = purchaseCreditNotePayload($supplier, $item, $unit, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote::with('lines')->findOrFail($payload['id']);
}

/**
 * User + company + the minimum masters a purchase return needs.
 *
 * It is the purchase invoice scenario plus the default location of the
 * warehouse: confirming a return writes an exit in the kardex, and the kardex
 * never moves stock without a place to take it from.
 *
 * @return array{
 *     0: \App\Modules\User\Models\User,
 *     1: \App\Modules\Company\Models\Company,
 *     2: \App\Modules\Supplier\Models\Supplier,
 *     3: \App\Modules\Warehouse\Models\Warehouse,
 *     4: \App\Modules\Item\Models\Item,
 *     5: \App\Modules\MeasurementUnit\Models\MeasurementUnit,
 *     6: \App\Modules\WarehouseLocation\Models\WarehouseLocation
 * }
 */
function purchaseReturnScenario(): array
{
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    \App\Modules\Warehouse\Models\Warehouse::where('id', $warehouse->id)->update(['uses_locations' => 'yes']);

    $location = \App\Modules\WarehouseLocation\Models\WarehouseLocation::factory()->default()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    return [$user, $company, $supplier, $warehouse, $item, $unit, $location->refresh()];
}

/**
 * A valid `purchase-returns.store` / `purchase-returns.update` payload.
 *
 * `purchase_invoice_id` is left out on purpose: the plain case is a return that
 * does not come from a particular invoice. The tests that need one pass it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function purchaseReturnPayload(
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
        'return_date' => now()->toDateString(),
        'reason' => 'damaged',
        'currency' => 'USD',
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 2,
                'unit_price' => 25,
            ],
        ],
        ...$overrides,
    ];
}

/**
 * Creates a purchase return over HTTP and returns the freshly saved model.
 *
 * @param  array<string, mixed>  $overrides
 */
function createPurchaseReturn(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\PurchaseReturn\Models\PurchaseReturn {
    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\PurchaseReturn\Models\PurchaseReturn::with('lines')->findOrFail($payload['id']);
}

/**
 * User + company + the minimum masters the kardex needs: an inventoried item,
 * a warehouse and one location of that warehouse.
 *
 * @return array{
 *     0: \App\Modules\Company\Models\Company,
 *     1: \App\Modules\Item\Models\Item,
 *     2: \App\Modules\Warehouse\Models\Warehouse,
 *     3: \App\Modules\WarehouseLocation\Models\WarehouseLocation,
 *     4: \App\Modules\User\Models\User
 * }
 */
function kardexScenario(string $allowsNegative = 'no'): array
{
    [$user, $company] = createUserWithCompany();

    $item = \App\Modules\Item\Models\Item::factory()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'type' => 'inventoried',
    ]);

    $warehouse = \App\Modules\Warehouse\Models\Warehouse::factory()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'allows_negative_stock' => $allowsNegative,
    ]);

    $location = \App\Modules\WarehouseLocation\Models\WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
        'created_by' => $user->id,
    ]);

    return [$company, $item, $warehouse, $location, $user];
}

/**
 * Registers one kardex movement through the service that documents use, so the
 * tests exercise the real path instead of writing the row by hand.
 *
 * @param  array<string, mixed>  $overrides
 */
function registerInventoryMovement(
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\WarehouseLocation\Models\WarehouseLocation $location,
    array $overrides = [],
): \App\Modules\InventoryMovement\Models\InventoryMovement {
    $service = app(\App\Modules\InventoryMovement\Services\InventoryMovementRegisterService::class);

    return $service->execute(new \App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand(
        companyId: $company->id,
        itemId: $item->id,
        warehouseId: $warehouse->id,
        locationId: $location->id,
        type: $overrides['type'] ?? 'in',
        originType: $overrides['originType'] ?? 'entry',
        originId: $overrides['originId'] ?? (string) \Illuminate\Support\Str::uuid7(),
        quantity: $overrides['quantity'] ?? 10,
        unitCost: $overrides['unitCost'] ?? null,
        movementDate: $overrides['movementDate'] ?? null,
        originLineId: $overrides['originLineId'] ?? null,
        lotId: $overrides['lotId'] ?? null,
        serialId: $overrides['serialId'] ?? null,
        notes: $overrides['notes'] ?? null,
        createdBy: $overrides['createdBy'] ?? null,
    ));
}

/**
 * User + company + the minimum masters a supplier payment needs. It is the
 * same scenario as a purchase invoice: what a payment cancels are invoices.
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
function supplierPaymentScenario(): array
{
    return purchaseOrderScenario();
}

/**
 * A purchase invoice that already owes money: created over HTTP and confirmed,
 * which is the only state in which a payment can be applied to it.
 *
 * @param  array<string, mixed>  $overrides
 */
function payablePurchaseInvoice(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\PurchaseInvoice\Models\PurchaseInvoice {
    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('purchase-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasNoErrors();

    return $invoice->refresh();
}

/**
 * A valid `supplier-payments.store` / `supplier-payments.update` payload.
 *
 * Without explicit applications it carries none: a payment can be registered
 * without distributing it yet.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function supplierPaymentPayload(
    \App\Modules\Supplier\Models\Supplier $supplier,
    array $overrides = [],
): array {
    return [
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'supplier_id' => $supplier->id,
        'origin_type' => 'supplier',
        'payment_date' => now()->toDateString(),
        'payment_method' => 'transfer',
        'currency' => 'USD',
        'amount' => 250,
        'applications' => [],
        ...$overrides,
    ];
}

/**
 * Creates a supplier payment over HTTP and returns the freshly saved model.
 *
 * @param  array<string, mixed>  $overrides
 */
function createSupplierPayment(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    array $overrides = [],
): \App\Modules\SupplierPayment\Models\SupplierPayment {
    $payload = supplierPaymentPayload($supplier, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('supplier-payments.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\SupplierPayment\Models\SupplierPayment::with('applications')->findOrFail($payload['id']);
}

/**
 * Moves a supplier payment to the given status over HTTP, which is what
 * actually posts or reverses its applications.
 *
 * @param  array<string, mixed>  $payload
 */
function moveSupplierPaymentTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\SupplierPayment\Models\SupplierPayment $payment,
    string $status,
    array $payload = [],
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('supplier-payments.update-status', ['company' => $company->id, 'id' => $payment->id]),
            ['status' => $status, ...$payload],
        );
}

/**
 * User + company + the minimum masters a supplier advance needs. It is the
 * same scenario as a purchase order: the order it stems from is optional, but
 * the tests that use it need one.
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
function supplierAdvanceScenario(): array
{
    return purchaseOrderScenario();
}

/**
 * A valid `supplier-advances.store` / `supplier-advances.update` payload.
 *
 * `purchase_order_id` is left out on purpose: the plain case is an advance
 * that does not stem from a particular order. The tests that need one pass it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function supplierAdvancePayload(
    \App\Modules\Supplier\Models\Supplier $supplier,
    array $overrides = [],
): array {
    return [
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'supplier_id' => $supplier->id,
        'advance_date' => now()->toDateString(),
        'payment_method' => 'transfer',
        'currency' => 'USD',
        'amount' => 400,
        ...$overrides,
    ];
}

/**
 * Creates a supplier advance over HTTP and returns the freshly saved model.
 *
 * @param  array<string, mixed>  $overrides
 */
function createSupplierAdvance(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    array $overrides = [],
): \App\Modules\SupplierAdvance\Models\SupplierAdvance {
    $payload = supplierAdvancePayload($supplier, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('supplier-advances.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\SupplierAdvance\Models\SupplierAdvance::findOrFail($payload['id']);
}

/**
 * Moves a supplier advance to the given status over HTTP, which is what
 * actually creates or cancels its mirror payment.
 */
function moveSupplierAdvanceTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\SupplierAdvance\Models\SupplierAdvance $advance,
    string $status,
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('supplier-advances.update-status', ['company' => $company->id, 'id' => $advance->id]),
            ['status' => $status],
        );
}

/**
 * An advance already approved and paid: the whole §5.1 path walked over HTTP,
 * which is the only way to reach `confirmed` and give the supplier credit.
 *
 * @param  array<string, mixed>  $overrides
 */
function confirmedSupplierAdvance(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    array $overrides = [],
): \App\Modules\SupplierAdvance\Models\SupplierAdvance {
    $advance = createSupplierAdvance($user, $company, $supplier, $overrides);

    moveSupplierAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    $payment = \App\Modules\SupplierPayment\Models\SupplierPayment::query()
        ->where('origin_type', 'advance')
        ->where('origin_id', $advance->id)
        ->firstOrFail();

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertSessionHasNoErrors();

    return $advance->refresh();
}
