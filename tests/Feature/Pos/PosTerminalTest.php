<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\Pos\Models\PosRegister;
use App\Modules\Pos\Models\PosSession;
use App\Modules\Tax\Models\Tax;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('pos terminal displays sellable items with warehouse stock and sku', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $tax = Tax::factory()->create([
        'company_id' => $company->id,
        'code' => 'IVA16',
        'name' => 'IVA 16%',
        'percentage' => 16.0,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $item->update([
        'code' => 'ART-001',
        'sku' => 'ITEM-INVENTARIADO-01',
        'name' => 'ITEM-INVENTARIADO-01',
        'is_sellable' => 'yes',
        'sale_tax_id' => $tax->id,
        'status' => 'active',
    ]);

    // Item not sellable should not appear
    $notSellableItem = Item::factory()->create([
        'company_id' => $company->id,
        'code' => 'ART-002',
        'name' => 'ITEM NO VENDIBLE',
        'is_sellable' => 'no',
        'status' => 'active',
    ]);

    // Create stock for the sellable item
    ItemStock::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 15.0000,
        'available_quantity' => 12.0000,
        'status' => 'active',
    ]);

    $register = PosRegister::create([
        'company_id' => $company->id,
        'code' => 'CAJ000001',
        'name' => 'Caja Mostrador',
        'warehouse_id' => $warehouse->id,
        'default_client_id' => $client->id,
        'created_by' => $user->id,
        'status' => 'active',
    ]);

    $session = PosSession::create([
        'company_id' => $company->id,
        'pos_register_id' => $register->id,
        'user_id' => $user->id,
        'code' => 'COR000001',
        'opened_at' => now(),
        'opening_cash_usd' => 100.0,
        'opening_cash_ves' => 0.0,
        'status' => 'confirmed',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('pos.terminal', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('pos/index')
        ->has('items', 1)
        ->where('items.0.id', $item->id)
        ->where('items.0.sku', 'ITEM-INVENTARIADO-01')
        ->where('items.0.name', 'ITEM-INVENTARIADO-01')
        ->where('items.0.code', 'ART-001')
        ->where('items.0.stock', 12)
        ->where('items.0.tax_id', $tax->id)
        ->where('items.0.tax_percent', 16)
        ->where('items.0.tax_name', 'IVA 16%')
    );
});
