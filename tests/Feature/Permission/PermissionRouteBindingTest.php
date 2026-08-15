<?php

declare(strict_types=1);

use App\Modules\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

function createPermission(): Permission
{
    $moduleId = (string) Str::uuid();

    DB::table('app_modules')->insert([
        'id' => $moduleId,
        'name' => 'module_'.Str::random(8),
        'label' => 'Module',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return Permission::create([
        'module_id' => $moduleId,
        'action' => 'view',
        'label' => 'View',
        'is_active' => true,
        'order' => 0,
    ]);
}

test('permissions.edit binds the permission id and not the company id', function () {
    [$user, $company] = createUserWithCompany();
    $permission = createPermission();

    expect($company->id)->not->toBe($permission->id);

    $response = actingAs($user)->get(route('permissions.edit', [
        'company' => $company->id,
        'id' => $permission->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Permissions/edit', false)
        ->where('item.id', $permission->id)
    );
});

test('permissions.show binds the permission id and not the company id', function () {
    [$user, $company] = createUserWithCompany();
    $permission = createPermission();

    $response = actingAs($user)->get(route('permissions.show', [
        'company' => $company->id,
        'id' => $permission->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Permissions/show', false)
        ->where('item.id', $permission->id)
    );
});
