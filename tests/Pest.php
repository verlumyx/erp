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
 * Moves a purchase order to the given status over HTTP, which is what actually
 * announces the incoming goods and writes the entry that will receive them.
 *
 * @param  array<string, mixed>  $payload  Extra fields, such as the reason a cancellation needs.
 */
function movePurchaseOrderTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\PurchaseOrder\Models\PurchaseOrder $order,
    string $status,
    array $payload = [],
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('purchase-orders.update-status', ['company' => $company->id, 'id' => $order->id]),
            ['status' => $status, ...$payload],
        );
}

/** El saldo del artículo en una ubicación concreta de la bodega. */
function stockAt(
    \App\Modules\Item\Models\Item $item,
    \App\Modules\WarehouseLocation\Models\WarehouseLocation $location,
): ?\App\Modules\ItemStock\Models\ItemStock {
    return \App\Modules\ItemStock\Models\ItemStock::query()
        ->where('item_id', $item->id)
        ->where('location_id', $location->id)
        ->first();
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
 * Moves a sales order to the given status over HTTP, which is what actually
 * reserves the goods and writes the dispatch that will take them out.
 *
 * @param  array<string, mixed>  $payload  Extra fields, such as the reason a cancellation needs.
 */
function moveSalesOrderTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\SalesOrder\Models\SalesOrder $order,
    string $status,
    array $payload = [],
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-orders.update-status', ['company' => $company->id, 'id' => $order->id]),
            ['status' => $status, ...$payload],
        );
}

/**
 * Un pedido confirmado, listo para despacharse y facturarse. Confirmarlo
 * reserva existencia, así que la bodega tiene que tener con qué, y genera el
 * despacho espejo que sacará la mercancía.
 */
function confirmedSalesOrder(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\SalesOrder\Models\SalesOrder {
    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, $overrides);

    moveSalesOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    return $order->refresh()->load('lines');
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
 * A sales invoice never moves stock: the goods leave with their dispatch. So no
 * test needs the warehouse stocked to confirm one.
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
 * warehouse: the return still records a location on each line, and the line
 * validates that it belongs to the warehouse. The return itself moves no stock.
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
 * A purchase invoice never moves stock: the goods arrive with their entry. What
 * these tests care about is the payable.
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

/**
 * User + company + the minimum masters a client collection needs. It is the
 * same scenario as a sales invoice: the invoices it settles come from there.
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
function clientCollectionScenario(): array
{
    return salesInvoiceScenario();
}

/**
 * A sales invoice that already owes money: created over HTTP and confirmed,
 * which is the only state in which a collection can be applied to it.
 *
 * @param  array<string, mixed>  $overrides
 */
function collectibleSalesInvoice(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\SalesInvoice\Models\SalesInvoice {
    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasNoErrors();

    return $invoice->refresh();
}

/**
 * A valid `client-collections.store` / `client-collections.update` payload.
 *
 * Without explicit applications it carries none: a collection can be registered
 * without distributing it yet.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function clientCollectionPayload(
    \App\Modules\Client\Models\Client $client,
    array $overrides = [],
): array {
    return [
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'client_id' => $client->id,
        'origin_type' => 'client',
        'collection_date' => now()->toDateString(),
        'payment_method' => 'transfer',
        'currency' => 'USD',
        'amount' => 250,
        'applications' => [],
        ...$overrides,
    ];
}

/**
 * Creates a client collection over HTTP and returns the freshly saved model.
 *
 * @param  array<string, mixed>  $overrides
 */
function createClientCollection(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    array $overrides = [],
): \App\Modules\ClientCollection\Models\ClientCollection {
    $payload = clientCollectionPayload($client, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('client-collections.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\ClientCollection\Models\ClientCollection::with('applications')->findOrFail($payload['id']);
}

/**
 * Moves a client collection to the given status over HTTP, which is what
 * actually posts or reverses its applications.
 *
 * @param  array<string, mixed>  $payload
 */
function moveClientCollectionTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\ClientCollection\Models\ClientCollection $collection,
    string $status,
    array $payload = [],
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-collections.update-status', ['company' => $company->id, 'id' => $collection->id]),
            ['status' => $status, ...$payload],
        );
}

/**
 * Moves the cheque of a collection along its own track.
 */
function moveClientCollectionCheckTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\ClientCollection\Models\ClientCollection $collection,
    string $checkStatus,
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-collections.update-check-status', ['company' => $company->id, 'id' => $collection->id]),
            ['check_status' => $checkStatus],
        );
}

/**
 * User + company + the minimum masters a client advance needs. It is the same
 * scenario as a sales order: the order it stems from is optional, but the tests
 * that use it need one.
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
function clientAdvanceScenario(): array
{
    return salesOrderScenario();
}

/**
 * A valid `client-advances.store` / `client-advances.update` payload.
 *
 * `sales_order_id` is left out on purpose: the plain case is an advance that
 * does not stem from a particular order. The tests that need one pass it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function clientAdvancePayload(
    \App\Modules\Client\Models\Client $client,
    array $overrides = [],
): array {
    return [
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'client_id' => $client->id,
        'advance_date' => now()->toDateString(),
        'payment_method' => 'transfer',
        'currency' => 'USD',
        'amount' => 400,
        ...$overrides,
    ];
}

/**
 * Creates a client advance over HTTP and returns the freshly saved model.
 *
 * @param  array<string, mixed>  $overrides
 */
function createClientAdvance(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    array $overrides = [],
): \App\Modules\ClientAdvance\Models\ClientAdvance {
    $payload = clientAdvancePayload($client, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('client-advances.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\ClientAdvance\Models\ClientAdvance::findOrFail($payload['id']);
}

/**
 * Moves a client advance to the given status over HTTP, which is what actually
 * creates or cancels its mirror collection.
 */
function moveClientAdvanceTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\ClientAdvance\Models\ClientAdvance $advance,
    string $status,
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-advances.update-status', ['company' => $company->id, 'id' => $advance->id]),
            ['status' => $status],
        );
}

/**
 * The mirror collection of an advance: the `COB` born out of approving it.
 */
function mirrorCollectionOf(
    \App\Modules\ClientAdvance\Models\ClientAdvance $advance,
): ?\App\Modules\ClientCollection\Models\ClientCollection {
    return \App\Modules\ClientCollection\Models\ClientCollection::query()
        ->where('origin_type', 'advance')
        ->where('origin_id', $advance->id)
        ->orderByDesc('created_at')
        ->first();
}

/**
 * An advance already approved and collected: the whole §5.1 path walked over
 * HTTP, which is the only way to reach `confirmed` and give the client credit.
 *
 * @param  array<string, mixed>  $overrides
 */
function confirmedClientAdvance(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    array $overrides = [],
): \App\Modules\ClientAdvance\Models\ClientAdvance {
    $advance = createClientAdvance($user, $company, $client, $overrides);

    moveClientAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    moveClientCollectionTo($user, $company, mirrorCollectionOf($advance), 'confirmed')
        ->assertSessionHasNoErrors();

    return $advance->refresh();
}

/**
 * User + company + the minimum masters a sales credit note needs.
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
function salesCreditNoteScenario(): array
{
    return salesOrderScenario();
}

/**
 * A valid `sales-credit-notes.store` / `sales-credit-notes.update` payload.
 *
 * `sales_invoice_id` is left out on purpose: the plain case is a note that does
 * not correct a particular invoice. The tests that need one pass it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function salesCreditNotePayload(
    \App\Modules\Client\Models\Client $client,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): array {
    return [
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'client_id' => $client->id,
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
 * Creates a sales credit note over HTTP and returns the freshly saved model.
 *
 * @param  array<string, mixed>  $overrides
 */
function createSalesCreditNote(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\SalesCreditNote\Models\SalesCreditNote {
    $payload = salesCreditNotePayload($client, $item, $unit, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\SalesCreditNote\Models\SalesCreditNote::with('lines')->findOrFail($payload['id']);
}

/**
 * User + company + the masters a sales return needs: a client, a warehouse
 * that uses locations with one default location, an inventoried item and its
 * base unit.
 *
 * The warehouse uses locations because the return records one on each line,
 * and because `dispatchScenario()` is an alias of this helper and dispatches do
 * move stock. The return itself moves none.
 *
 * @return array{
 *     0: \App\Modules\User\Models\User,
 *     1: \App\Modules\Company\Models\Company,
 *     2: \App\Modules\Client\Models\Client,
 *     3: \App\Modules\Warehouse\Models\Warehouse,
 *     4: \App\Modules\Item\Models\Item,
 *     5: \App\Modules\MeasurementUnit\Models\MeasurementUnit,
 *     6: \App\Modules\WarehouseLocation\Models\WarehouseLocation
 * }
 */
function salesReturnScenario(): array
{
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    \App\Modules\Warehouse\Models\Warehouse::where('id', $warehouse->id)->update(['uses_locations' => 'yes']);

    $location = \App\Modules\WarehouseLocation\Models\WarehouseLocation::factory()->default()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    return [$user, $company, $client, $warehouse->refresh(), $item, $unit, $location->refresh()];
}

/**
 * A valid `sales-returns.store` / `sales-returns.update` payload.
 *
 * `sales_invoice_id` is left out on purpose: the plain case is a return that
 * does not come from a particular invoice. The tests that need one pass it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function salesReturnPayload(
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
        'return_date' => now()->toDateString(),
        'reason' => 'damaged',
        'condition' => 'resalable',
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
 * Creates a sales return over HTTP and returns the freshly saved model.
 *
 * @param  array<string, mixed>  $overrides
 */
function createSalesReturn(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\SalesReturn\Models\SalesReturn {
    $payload = salesReturnPayload($client, $warehouse, $item, $unit, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\SalesReturn\Models\SalesReturn::with('lines')->findOrFail($payload['id']);
}

/**
 * Moves a sales return to the given status over HTTP, which is what actually
 * consumes the returned quota of the invoice line, or gives it back.
 */
function moveSalesReturnTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\SalesReturn\Models\SalesReturn $return,
    string $status,
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]),
            ['status' => $status],
        );
}

/**
 * User + company + the minimum masters a dispatch needs, plus a warehouse that
 * uses locations and has a default one: sin ubicación por defecto el kardex no
 * puede sacar la mercancía.
 *
 * @return array{
 *     0: \App\Modules\User\Models\User,
 *     1: \App\Modules\Company\Models\Company,
 *     2: \App\Modules\Client\Models\Client,
 *     3: \App\Modules\Warehouse\Models\Warehouse,
 *     4: \App\Modules\Item\Models\Item,
 *     5: \App\Modules\MeasurementUnit\Models\MeasurementUnit,
 *     6: \App\Modules\WarehouseLocation\Models\WarehouseLocation
 * }
 */
function dispatchScenario(): array
{
    return salesReturnScenario();
}

/**
 * A valid `dispatches.store` / `dispatches.update` payload.
 *
 * `sourceable_type` / `sourceable_id` are left out on purpose: the plain case
 * is a direct dispatch with no order behind it. The tests that need one pass
 * them.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function dispatchPayload(
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
        'dispatch_date' => now()->toDateString(),
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
 * Creates a dispatch over HTTP and returns the freshly saved model.
 *
 * @param  array<string, mixed>  $overrides
 */
function createDispatch(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\Dispatch\Models\Dispatch {
    $payload = dispatchPayload($client, $warehouse, $item, $unit, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('dispatches.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\Dispatch\Models\Dispatch::with('lines')->findOrFail($payload['id']);
}

/**
 * Moves a dispatch to the given status over HTTP, which is what actually posts
 * it to the kardex or reverses it.
 */
function moveDispatchTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Dispatch\Models\Dispatch $dispatch,
    string $status,
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('dispatches.update-status', ['company' => $company->id, 'id' => $dispatch->id]),
            ['status' => $status],
        );
}

/**
 * Registers how the trip ended over HTTP: it is what brings back whatever the
 * client did not keep.
 *
 * @param  array<string, mixed>  $payload
 */
function registerDispatchDelivery(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Dispatch\Models\Dispatch $dispatch,
    array $payload = [],
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('dispatches.delivery', ['company' => $company->id, 'id' => $dispatch->id]),
            ['delivery_status' => 'delivered', ...$payload],
        );
}

/**
 * The live kardex movements a dispatch wrote, counter-entries included: the
 * tests that check a cancellation need to see both sides.
 */
function dispatchMovements(
    \App\Modules\Dispatch\Models\Dispatch $dispatch,
): \Illuminate\Database\Eloquent\Collection {
    return \App\Modules\InventoryMovement\Models\InventoryMovement::query()
        ->where('origin_type', \App\Modules\Dispatch\Models\Dispatch::MOVEMENT_ORIGIN_TYPE)
        ->where('origin_id', $dispatch->id)
        ->orderBy('created_at')
        ->get();
}

/**
 * User + company + the masters an entry needs: a supplier, a warehouse that
 * uses locations with one default location, a purchasable item and its base
 * unit.
 *
 * The warehouse uses locations because confirming an entry writes to the
 * kardex, and the kardex does not move stock without a location.
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
function entryScenario(): array
{
    $scenario = purchaseReturnScenario();

    /**
     * The item already has an average cost. The entry screen does not capture
     * the cost any more: without a purchase order behind it, a receipt is
     * valued at whatever the item is already worth.
     */
    $scenario[4]->update(['average_cost' => 25]);

    return $scenario;
}

/**
 * A valid `entries.store` / `entries.update` payload.
 *
 * The source purchase order is left out on purpose: the plain case is a receipt
 * that does not come from a particular order. The tests that need one pass it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function entryPayload(
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
        'entry_date' => now()->toDateString(),
        'entry_type' => 'purchase',
        'inspection_status' => 'pending',
        'currency' => 'USD',
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
            ],
        ],
        ...$overrides,
    ];
}

/**
 * Creates an entry over HTTP and returns the freshly saved model.
 *
 * @param  array<string, mixed>  $overrides
 */
function createEntry(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\Entry\Models\Entry {
    $payload = entryPayload($supplier, $warehouse, $item, $unit, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\Entry\Models\Entry::with('lines')->findOrFail($payload['id']);
}

/**
 * Moves an entry to the given status over HTTP, which is what actually posts it
 * to the kardex or reverses it.
 */
function moveEntryTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Entry\Models\Entry $entry,
    string $status,
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('entries.update-status', ['company' => $company->id, 'id' => $entry->id]),
            ['status' => $status],
        );
}

/**
 * Kardex movements written by an entry, in the order they were registered.
 *
 * @return \Illuminate\Database\Eloquent\Collection<int, \App\Modules\InventoryMovement\Models\InventoryMovement>
 */
function entryMovements(\App\Modules\Entry\Models\Entry $entry): \Illuminate\Database\Eloquent\Collection
{
    return \App\Modules\InventoryMovement\Models\InventoryMovement::query()
        ->where('origin_type', \App\Modules\Entry\Models\Entry::MOVEMENT_ORIGIN_TYPE)
        ->where('origin_id', $entry->id)
        ->orderBy('created_at')
        ->get();
}

/**
 * User + company + the masters a transfer needs: two warehouses that use
 * locations, each with a default one, plus an item and its base unit.
 *
 * Both warehouses use locations because a transfer moves stock through the
 * dispatch it generates at the origin and the entry it generates at the
 * destination, and neither writes to the kardex without a location.
 *
 * @return array{
 *     0: \App\Modules\User\Models\User,
 *     1: \App\Modules\Company\Models\Company,
 *     2: \App\Modules\Warehouse\Models\Warehouse,
 *     3: \App\Modules\WarehouseLocation\Models\WarehouseLocation,
 *     4: \App\Modules\Warehouse\Models\Warehouse,
 *     5: \App\Modules\WarehouseLocation\Models\WarehouseLocation,
 *     6: \App\Modules\Item\Models\Item,
 *     7: \App\Modules\MeasurementUnit\Models\MeasurementUnit
 * }
 */
function transferScenario(): array
{
    [$user, $company, , $origin, $item, $unit, $originLocation] = purchaseReturnScenario();

    [$destination, $destinationLocation] = warehouseWithDefaultLocation($company, $user);

    return [
        $user, $company,
        $origin, $originLocation,
        $destination, $destinationLocation,
        $item, $unit,
    ];
}

/**
 * A warehouse that uses locations plus its default one. Without
 * `uses_locations = yes` the location repository hides it and no document can
 * resolve a default place to move the stock to.
 *
 * @return array{0: \App\Modules\Warehouse\Models\Warehouse, 1: \App\Modules\WarehouseLocation\Models\WarehouseLocation}
 */
function warehouseWithDefaultLocation(
    \App\Modules\Company\Models\Company $company,
    \App\Modules\User\Models\User $user,
): array {
    $warehouse = \App\Modules\Warehouse\Models\Warehouse::factory()->create([
        'company_id' => $company->id,
        'uses_locations' => 'yes',
    ]);

    $location = \App\Modules\WarehouseLocation\Models\WarehouseLocation::factory()->default()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    return [$warehouse->refresh(), $location->refresh()];
}

/**
 * A valid `transfers.store` / `transfers.update` payload.
 *
 * `transit_warehouse_id` is left out on purpose: the plain case is an immediate
 * transfer, where the goods leave and arrive in the same act. The tests that
 * need the two-step flow pass it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function transferPayload(
    \App\Modules\Warehouse\Models\Warehouse $origin,
    \App\Modules\Warehouse\Models\Warehouse $destination,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): array {
    return [
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'origin_warehouse_id' => $origin->id,
        'destination_warehouse_id' => $destination->id,
        'transfer_date' => now()->toDateString(),
        'reason' => 'restock',
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 2,
            ],
        ],
        ...$overrides,
    ];
}

/**
 * Creates a transfer over HTTP and returns the freshly saved model.
 *
 * @param  array<string, mixed>  $overrides
 */
function createTransfer(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Warehouse\Models\Warehouse $origin,
    \App\Modules\Warehouse\Models\Warehouse $destination,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\Transfer\Models\Transfer {
    $payload = transferPayload($origin, $destination, $item, $unit, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('transfers.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\Transfer\Models\Transfer::with('lines')->findOrFail($payload['id']);
}

/**
 * Moves a transfer to the given status over HTTP, which is what actually
 * generates its dispatch, or reverses it.
 */
function moveTransferTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Transfer\Models\Transfer $transfer,
    string $status,
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('transfers.update-status', ['company' => $company->id, 'id' => $transfer->id]),
            ['status' => $status],
        );
}

/**
 * The live kardex movements a transfer caused, counter-entries included. It
 * writes none itself: they belong to the dispatch it generates at the origin
 * and the entry at the destination, and the cancellation tests need both sides.
 *
 * @return \Illuminate\Database\Eloquent\Collection<int, \App\Modules\InventoryMovement\Models\InventoryMovement>
 */
function transferMovements(
    \App\Modules\Transfer\Models\Transfer $transfer,
): \Illuminate\Database\Eloquent\Collection {
    $dispatch = transferDispatch($transfer);

    $entry = $dispatch === null ? null : dispatchEntry($dispatch);

    return \App\Modules\InventoryMovement\Models\InventoryMovement::query()
        ->where(function ($query) use ($dispatch, $entry): void {
            $query->whereRaw('1 = 0');

            if ($dispatch !== null) {
                $query->orWhere(fn ($q) => $q
                    ->where('origin_type', \App\Modules\Dispatch\Models\Dispatch::MOVEMENT_ORIGIN_TYPE)
                    ->where('origin_id', $dispatch->id));
            }

            if ($entry !== null) {
                $query->orWhere(fn ($q) => $q
                    ->where('origin_type', \App\Modules\Entry\Models\Entry::MOVEMENT_ORIGIN_TYPE)
                    ->where('origin_id', $entry->id));
            }
        })
        ->orderBy('code')
        ->get();
}

/** Deja existencia en una bodega, al costo indicado, para poder sacarla luego. */
function stockWarehouse(
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\WarehouseLocation\Models\WarehouseLocation $location,
    float $quantity = 20,
    float $unitCost = 10,
): void {
    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => $quantity,
        'unitCost' => $unitCost,
    ]);
}

/** El despacho que el traslado generó al confirmarse, si lo generó. */
function transferDispatch(
    \App\Modules\Transfer\Models\Transfer $transfer,
): ?\App\Modules\Dispatch\Models\Dispatch {
    return \App\Modules\Dispatch\Models\Dispatch::query()
        ->with('lines')
        ->where('sourceable_type', \App\Modules\Transfer\Models\Transfer::MORPH_ALIAS)
        ->where('sourceable_id', $transfer->id)
        ->where('status', '!=', 'cancelled')
        ->first();
}

/**
 * La entrada que el despacho generó al confirmarse, si la generó. Cuelga del
 * traslado —el documento que originó el movimiento—, no del despacho.
 */
function dispatchEntry(
    \App\Modules\Dispatch\Models\Dispatch $dispatch,
): ?\App\Modules\Entry\Models\Entry {
    $transfer = $dispatch->sourceable;

    return $transfer instanceof \App\Modules\Transfer\Models\Transfer
        ? transferEntry($transfer)
        : null;
}

/** La entrada con la que el traslado llega al destino, si ya nació. */
function transferEntry(
    \App\Modules\Transfer\Models\Transfer $transfer,
): ?\App\Modules\Entry\Models\Entry {
    return \App\Modules\Entry\Models\Entry::query()
        ->with('lines')
        ->where('sourceable_type', \App\Modules\Transfer\Models\Transfer::MORPH_ALIAS)
        ->where('sourceable_id', $transfer->id)
        ->where('status', '!=', 'cancelled')
        ->first();
}

/**
 * Narrows what the user can do in the company to exactly the given permissions.
 * The scenarios hand out a role with `permission_type = all`, so this is the
 * only way to test that a guarded action is really guarded.
 *
 * @param  array<int, string>  $permissions
 */
function restrictPermissions(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    array $permissions,
): void {
    $role = \App\Modules\Role\Models\Role::create([
        'company_id' => $company->id,
        'name' => 'Acceso Limitado '.uniqid(),
        'status' => 'active',
        'permission_type' => 'specific',
    ]);

    foreach ($permissions as $permission) {
        \App\Modules\Role\Models\RolePermission::create([
            'role_id' => $role->id,
            'permission' => $permission,
        ]);
    }

    $user->companies()->updateExistingPivot($company->id, ['role_id' => $role->id]);
}

/**
 * The stock balance of an item in a warehouse, as the kardex left it.
 */
function warehouseBalance(
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
): float {
    return (float) \App\Modules\ItemStock\Models\ItemStock::query()
        ->where('company_id', $company->id)
        ->where('item_id', $item->id)
        ->where('warehouse_id', $warehouse->id)
        ->sum('quantity');
}

/**
 * The stock balance of one lot of an item in a warehouse. Stock is kept per
 * lot, so this is what tells apart a correction that landed on the right one.
 */
function lotBalance(
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    string $lotId,
): float {
    return (float) \App\Modules\ItemStock\Models\ItemStock::query()
        ->where('company_id', $company->id)
        ->where('item_id', $item->id)
        ->where('warehouse_id', $warehouse->id)
        ->where('lot_id', $lotId)
        ->sum('quantity');
}

/**
 * User + company + the masters an adjustment needs: a warehouse that uses
 * locations with one default location, an item and its base unit.
 *
 * The warehouse uses locations because confirming an adjustment writes to the
 * kardex, and the kardex does not move stock without a location.
 *
 * The approval threshold is raised on purpose: the plain scenario is a small
 * adjustment that one person registers and applies. The tests that care about
 * the second signature lower it with `setAdjustmentThreshold()`.
 *
 * @return array{
 *     0: \App\Modules\User\Models\User,
 *     1: \App\Modules\Company\Models\Company,
 *     2: \App\Modules\Warehouse\Models\Warehouse,
 *     3: \App\Modules\WarehouseLocation\Models\WarehouseLocation,
 *     4: \App\Modules\Item\Models\Item,
 *     5: \App\Modules\MeasurementUnit\Models\MeasurementUnit
 * }
 */
function adjustmentScenario(): array
{
    [$user, $company, , $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    setAdjustmentThreshold($company, 1000000);

    return [$user, $company, $warehouse, $location, $item, $unit];
}

/** Impacto a partir del cual el ajuste necesita una segunda firma. */
function setAdjustmentThreshold(
    \App\Modules\Company\Models\Company $company,
    float $threshold,
): void {
    \App\Modules\Configuration\Models\Configuration::query()
        ->where('company_id', $company->id)
        ->update(['adjustment_approval_threshold' => $threshold]);
}

/**
 * A valid `adjustments.store` / `adjustments.update` payload.
 *
 * The line only carries what is actually counted: the system quantity and the
 * cost are resolved by the backend, so a payload that tried to send them would
 * be sending noise.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function adjustmentPayload(
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): array {
    return [
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'warehouse_id' => $warehouse->id,
        'adjustment_date' => now()->toDateString(),
        'type' => 'physical_count',
        'direction' => 'mixed',
        'reason' => 'Conteo físico de fin de mes.',
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'counted_quantity' => 12,
            ],
        ],
        ...$overrides,
    ];
}

/**
 * Creates an adjustment over HTTP and returns the freshly saved model.
 *
 * @param  array<string, mixed>  $overrides
 */
function createAdjustment(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\Adjustment\Models\Adjustment {
    $payload = adjustmentPayload($warehouse, $item, $unit, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('adjustments.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\Adjustment\Models\Adjustment::with('lines')->findOrFail($payload['id']);
}

/**
 * Moves an adjustment to the given status over HTTP, which is what actually
 * applies it to the kardex or reverses it.
 */
function moveAdjustmentTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Adjustment\Models\Adjustment $adjustment,
    string $status,
    ?string $cancellationReason = null,
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('adjustments.update-status', ['company' => $company->id, 'id' => $adjustment->id]),
            array_filter([
                'status' => $status,
                'cancellation_reason' => $cancellationReason,
            ], fn ($value): bool => $value !== null),
        );
}

/**
 * Sends an adjustment all the way through to `confirmed`, which is the only
 * status in which it touches the stock.
 */
function applyAdjustment(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Adjustment\Models\Adjustment $adjustment,
): \Illuminate\Testing\TestResponse {
    moveAdjustmentTo($user, $company, $adjustment, 'pending_approval')
        ->assertSessionHasNoErrors();

    return moveAdjustmentTo($user, $company, $adjustment->refresh(), 'confirmed');
}

/**
 * Kardex movements written by an adjustment, in the order they were registered.
 *
 * @return \Illuminate\Database\Eloquent\Collection<int, \App\Modules\InventoryMovement\Models\InventoryMovement>
 */
function adjustmentMovements(
    \App\Modules\Adjustment\Models\Adjustment $adjustment,
): \Illuminate\Database\Eloquent\Collection {
    return \App\Modules\InventoryMovement\Models\InventoryMovement::query()
        ->where('origin_type', \App\Modules\Adjustment\Models\Adjustment::MOVEMENT_ORIGIN_TYPE)
        ->where('origin_id', $adjustment->id)
        ->orderBy('created_at')
        ->get();
}

/**
 * User + company + the masters a route needs: an active client with a default
 * address and a warehouse from which the load leaves.
 *
 * @return array{
 *     0: \App\Modules\User\Models\User,
 *     1: \App\Modules\Company\Models\Company,
 *     2: \App\Modules\Client\Models\Client,
 *     3: \App\Modules\Warehouse\Models\Warehouse
 * }
 */
function routeScenario(): array
{
    [$user, $company] = createUserWithCompany();

    $client = \App\Modules\Client\Models\Client::factory()->create(['company_id' => $company->id]);
    $warehouse = \App\Modules\Warehouse\Models\Warehouse::factory()->create(['company_id' => $company->id]);

    return [$user, $company, $client, $warehouse];
}

/**
 * A valid `routes.store` / `routes.update` payload.
 *
 * The plain case is a weekly delivery route with no vehicle capacity declared:
 * with nothing declared, planning a day cannot reject anything by weight. The
 * tests that need the check pass the capacities.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function routePayload(array $overrides = []): array
{
    return [
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'name' => 'Zona Norte - Lunes '.uniqid(),
        'type' => 'delivery',
        'frequency' => 'weekly',
        'weekdays' => ['mon', 'wed'],
        'clients' => [],
        ...$overrides,
    ];
}

/**
 * Creates a route over HTTP and returns the freshly saved model.
 *
 * @param  array<string, mixed>  $overrides
 */
function createRoute(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    array $overrides = [],
): \App\Modules\Route\Models\Route {
    $payload = routePayload($overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('routes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\Route\Models\Route::with('clients')->findOrFail($payload['id']);
}

/**
 * Generates the stops of a day over HTTP: it is what turns the template plus
 * the pending dispatches into an actual itinerary.
 */
function planRoute(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Route\Models\Route $route,
    ?string $stopDate = null,
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(
            route('routes.plan', ['company' => $company->id, 'id' => $route->id]),
            ['stop_date' => $stopDate ?? now()->toDateString()],
        );
}

/**
 * Registers what happened at one stop over HTTP.
 *
 * @param  array<string, mixed>  $payload
 */
function registerRouteStopVisit(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Route\Models\Route $route,
    \App\Modules\Route\Models\RouteStop $stop,
    array $payload = [],
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('routes.stops.visit', [
                'company' => $company->id,
                'id' => $route->id,
                'stop' => $stop->id,
            ]),
            ['stop_status' => 'completed', ...$payload],
        );
}

/**
 * Stops of a route on a given day, in visiting order.
 *
 * @return \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Route\Models\RouteStop>
 */
function routeStopsOn(
    \App\Modules\Route\Models\Route $route,
    ?string $stopDate = null,
): \Illuminate\Database\Eloquent\Collection {
    return \App\Modules\Route\Models\RouteStop::query()
        ->where('route_id', $route->id)
        ->whereDate('stop_date', $stopDate ?? now()->toDateString())
        ->where('status', 'active')
        ->orderBy('sequence')
        ->get();
}

/**
 * Moves a sales credit note to the given status over HTTP, which is what
 * actually posts its balance and its credit to the invoice.
 */
function moveSalesCreditNoteTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\SalesCreditNote\Models\SalesCreditNote $note,
    string $status,
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]),
            ['status' => $status],
        );
}

/**
 * Moves a purchase credit note to the given status over HTTP, which is what
 * actually posts its balance and its credit to the invoice.
 */
function movePurchaseCreditNoteTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote $note,
    string $status,
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('purchase-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]),
            ['status' => $status],
        );
}

/**
 * Issues a sales invoice over HTTP, which is what burns its fiscal number,
 * posts the receivable and freezes the cost of the goods sold.
 */
function confirmSalesInvoice(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\SalesInvoice\Models\SalesInvoice $invoice,
): void {
    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasNoErrors();
}

/**
 * Puts stock in the warehouse of a sales order scenario, which is what
 * confirming an order needs: a sales order does not take goods out, it reserves
 * them, and it cannot reserve what the warehouse does not have.
 *
 * The warehouse starts using locations and gets a default one, because that is
 * where the reservation is written.
 */
function stockSalesOrderWarehouse(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    float $quantity = 100,
): \App\Modules\WarehouseLocation\Models\WarehouseLocation {
    \App\Modules\Warehouse\Models\Warehouse::where('id', $warehouse->id)
        ->update(['uses_locations' => 'yes']);

    $location = \App\Modules\WarehouseLocation\Models\WarehouseLocation::query()
        ->where('warehouse_id', $warehouse->id)
        ->where('is_default', 'yes')
        ->first()
        ?? \App\Modules\WarehouseLocation\Models\WarehouseLocation::factory()->default()->create([
            'company_id' => $company->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
        ]);

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => $quantity,
        'unitCost' => 10,
    ]);

    return $location->refresh();
}

/**
 * Tienda en línea: usuario + empresa (USD/VES) con los ajustes de tienda
 * encendidos y una llave conocida (`stk_test-key`), más la lista de precio,
 * la bodega y la unidad con las que se publica un artículo.
 *
 * @return array{
 *     0: \App\Modules\User\Models\User,
 *     1: \App\Modules\Company\Models\Company,
 *     2: \App\Modules\Store\Models\StoreSetting,
 *     3: \App\Modules\PriceList\Models\PriceList,
 *     4: \App\Modules\Warehouse\Models\Warehouse,
 *     5: \App\Modules\MeasurementUnit\Models\MeasurementUnit
 * }
 */
function storeScenario(): array
{
    [$user, $company] = createUserWithCompany();

    $priceList = \App\Modules\PriceList\Models\PriceList::factory()
        ->create(['company_id' => $company->id]);

    $warehouse = \App\Modules\Warehouse\Models\Warehouse::factory()
        ->create(['company_id' => $company->id]);

    $unit = \App\Modules\MeasurementUnit\Models\MeasurementUnit::factory()
        ->create(['company_id' => $company->id]);

    $settings = \App\Modules\Store\Models\StoreSetting::factory()
        ->enabledWithKey()
        ->create([
            'company_id' => $company->id,
            'price_list_id' => $priceList->id,
            'warehouse_id' => null,
            'created_by' => $user->id,
        ]);

    return [$user, $company, $settings, $priceList, $warehouse, $unit];
}

/**
 * Artículo vendible de la empresa con su unidad base, ya publicado en la
 * tienda. Devuelve el artículo y su publicación.
 *
 * @param  array<string, mixed>  $itemOverrides
 * @param  array<string, mixed>  $storeItemOverrides
 * @return array{0: \App\Modules\Item\Models\Item, 1: \App\Modules\Store\Models\StoreItem}
 */
function publishedItem(
    \App\Modules\Company\Models\Company $company,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $itemOverrides = [],
    array $storeItemOverrides = [],
): array {
    $item = \App\Modules\Item\Models\Item::factory()
        ->create(['company_id' => $company->id, ...$itemOverrides]);

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $storeItem = \App\Modules\Store\Models\StoreItem::factory()
        ->create(['company_id' => $company->id, 'item_id' => $item->id, ...$storeItemOverrides]);

    return [$item, $storeItem];
}

/** Cabecera con la llave de `storeScenario()`. */
function storeKeyHeaders(string $key = 'stk_test-key'): array
{
    return ['X-Store-Key' => $key, 'Accept' => 'application/json'];
}

/**
 * Tienda encendida con llave conocida (`stk_test-key`), lista de precio,
 * bodega y un producto visible con precio y unidad base. Es lo mínimo para
 * que un comprador pueda pedir algo.
 *
 * @return array{
 *     user: \App\Modules\User\Models\User,
 *     company: \App\Modules\Company\Models\Company,
 *     settings: \App\Modules\Store\Models\StoreSetting,
 *     price_list: \App\Modules\PriceList\Models\PriceList,
 *     warehouse: \App\Modules\Warehouse\Models\Warehouse,
 *     unit: \App\Modules\MeasurementUnit\Models\MeasurementUnit,
 *     item: \App\Modules\Item\Models\Item,
 *     store_item: \App\Modules\Store\Models\StoreItem,
 * }
 */
function storeOrderScenario(array $settingsOverrides = []): array
{
    [$user, $company] = createUserWithCompany();

    $priceList = \App\Modules\PriceList\Models\PriceList::factory()->create(['company_id' => $company->id]);
    $warehouse = \App\Modules\Warehouse\Models\Warehouse::factory()->create(['company_id' => $company->id]);
    $unit = \App\Modules\MeasurementUnit\Models\MeasurementUnit::factory()->create(['company_id' => $company->id]);

    $item = \App\Modules\Item\Models\Item::factory()->create(['company_id' => $company->id]);

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);

    \App\Modules\Item\Models\ItemPrice::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'price_list_id' => $priceList->id,
        'price' => 45,
        'currency' => 'USD',
    ]);

    $storeItem = \App\Modules\Store\Models\StoreItem::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'created_by' => $user->id,
    ]);

    $settings = \App\Modules\Store\Models\StoreSetting::factory()
        ->enabledWithKey()
        ->allowingOrders()
        ->create([
            'company_id' => $company->id,
            'price_list_id' => $priceList->id,
            'warehouse_id' => $warehouse->id,
            'store_url' => 'https://tienda.test',
            'created_by' => $user->id,
            ...$settingsOverrides,
        ]);

    todayExchangeRate($company, $user);

    return [
        'user' => $user,
        'company' => $company,
        'settings' => $settings,
        'price_list' => $priceList,
        'warehouse' => $warehouse,
        'unit' => $unit,
        'item' => $item,
        'store_item' => $storeItem,
    ];
}

/** Token Sanctum de un comprador con la habilidad de la tienda. */
function storeCustomerToken(\App\Modules\Store\Models\StoreCustomer $customer): string
{
    return $customer->createToken('test', [\App\Modules\Store\Models\StoreCustomer::TOKEN_ABILITY])->plainTextToken;
}

/** Cabeceras con las que la tienda habla con la API pública. */
function storeApiHeaders(?\App\Modules\Store\Models\StoreCustomer $customer = null, string $key = 'stk_test-key'): array
{
    $headers = ['X-Store-Key' => $key, 'Accept' => 'application/json'];

    if ($customer !== null) {
        $headers['Authorization'] = 'Bearer '.storeCustomerToken($customer);
    }

    return $headers;
}

/**
 * User + company + the masters an import file needs: a warehouse that uses
 * locations with a default one, a supplier and an item with its base unit.
 *
 * The warehouse uses locations because the file ends in a revaluation
 * adjustment, and the kardex does not move value without a place to move it in.
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
function importScenario(): array
{
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    /** Sin promedio previo la entrada no tiene con qué valorar lo que recibe. */
    $item->update(['average_cost' => 25]);

    setAdjustmentThreshold($company, 1000000);

    return [$user, $company, $supplier, $warehouse, $item, $unit, $location->refresh()];
}

/**
 * A confirmed entry: the only kind an import file can cost, because a draft has
 * not valued anything yet.
 *
 * @param  array<string, mixed>  $overrides
 */
function costableEntry(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \App\Modules\Entry\Models\Entry {
    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, $overrides);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    return $entry->refresh();
}

/**
 * A valid `imports.store` / `imports.update` payload.
 *
 * Neither the items nor what each one absorbs travel in it: they are derived
 * from the receipts, so a payload that tried to send them would be sending
 * noise.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function importPayload(
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Entry\Models\Entry $entry,
    array $overrides = [],
): array {
    return [
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'warehouse_id' => $warehouse->id,
        'import_date' => now()->toDateString(),
        'allocation_method' => 'value',
        'currency' => 'USD',
        'reference' => 'BL-0001',
        'costs' => [
            [
                'concept' => 'freight',
                'currency' => 'USD',
                'amount' => 100,
            ],
        ],
        'entries' => [
            ['entry_id' => $entry->id],
        ],
        ...$overrides,
    ];
}

/**
 * Creates an import file over HTTP and returns the freshly saved model.
 *
 * @param  array<string, mixed>  $overrides
 */
function createImport(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Entry\Models\Entry $entry,
    array $overrides = [],
): \App\Modules\Import\Models\Import {
    $payload = importPayload($warehouse, $entry, $overrides);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('imports.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return \App\Modules\Import\Models\Import::with(['costs', 'entries', 'lines.lots'])
        ->findOrFail($payload['id']);
}

/**
 * Moves an import file to the given status over HTTP, which is what actually
 * generates —or retires— the revaluation adjustment.
 */
function moveImportTo(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Import\Models\Import $import,
    string $status,
    ?string $cancellationReason = null,
): \Illuminate\Testing\TestResponse {
    return \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('imports.update-status', ['company' => $company->id, 'id' => $import->id]),
            array_filter([
                'status' => $status,
                'cancellation_reason' => $cancellationReason,
            ], fn ($value): bool => $value !== null),
        );
}

/** El ajuste de revaluación que el expediente generó al confirmarse. */
function importAdjustment(
    \App\Modules\Import\Models\Import $import,
): ?\App\Modules\Adjustment\Models\Adjustment {
    return \App\Modules\Adjustment\Models\Adjustment::with('lines.lots')
        ->find($import->refresh()->adjustment_id);
}
