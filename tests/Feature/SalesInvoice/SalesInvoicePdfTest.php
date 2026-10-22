<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

test('a sales invoice pdf can be generated and streamed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('sales-invoices.pdf', ['company' => $company->id, 'id' => $invoice->id]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain("factura-{$invoice->code}.pdf");
});

test('a user without permission cannot download the invoice pdf', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $role = \App\Modules\Role\Models\Role::create([
        'company_id' => $company->id,
        'name' => 'No access ' . uniqid(),
        'status' => 'active',
        'permission_type' => 'specific',
    ]);

    $user->companies()->updateExistingPivot($company->id, ['role_id' => $role->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('sales-invoices.pdf', ['company' => $company->id, 'id' => $invoice->id]))
        ->assertForbidden();
});
