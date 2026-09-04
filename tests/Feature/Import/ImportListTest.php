<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list shows the import files of the company', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $first = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);
    $second = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);

    createImport($user, $company, $warehouse, $first);
    createImport($user, $company, $warehouse, $second, ['reference' => 'AWB-77']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('imports.index', ['company' => $company->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('imports/index')
            ->has('imports', 2)
            ->where('meta.total', 2)
            ->has('warehouses')
        );
});

test('the list filters by reference', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $first = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);
    $second = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);

    createImport($user, $company, $warehouse, $first);
    createImport($user, $company, $warehouse, $second, ['reference' => 'AWB-77']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('imports.index', ['company' => $company->id, 'reference' => 'AWB']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('imports/index')
            ->has('imports', 1)
        );
});

test('the detail screen carries the costs, the receipts and the derived items', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);
    $import = createImport($user, $company, $warehouse, $entry);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('imports.show', ['company' => $company->id, 'id' => $import->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('imports/show')
            ->where('import.code', 'IMP000001')
            ->has('import.costs', 1)
            ->has('import.entries', 1)
            ->has('import.lines', 1)
            ->has('import.lines.0.lots')
        );
});

test('the receipt lookup hides an entry already taken by a live file', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $taken = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);
    $free = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);

    createImport($user, $company, $warehouse, $taken);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('imports.entries', [
            'company' => $company->id,
            'warehouse_id' => $warehouse->id,
        ]));

    $response->assertOk();

    expect(array_column($response->json('data'), 'value'))->toBe([$free->id]);
});

test('a draft entry is not offered to be costed', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('imports.entries', [
            'company' => $company->id,
            'warehouse_id' => $warehouse->id,
        ]));

    $response->assertOk();

    expect($response->json('data'))->toBe([]);
});

test('a user without the module permissions cannot reach it', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);
    $import = createImport($user, $company, $warehouse, $entry);

    restrictPermissions($user, $company, []);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('imports.index', ['company' => $company->id]))
        ->assertForbidden();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('imports.show', ['company' => $company->id, 'id' => $import->id]))
        ->assertForbidden();
});
