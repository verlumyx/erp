<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Store\Models\StoreItem;
use App\Modules\Store\Models\StoreItemImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

function photoUpload(string $name = 'photo.png'): UploadedFile
{
    return new UploadedFile(base_path('tests/Fixtures/store/photo.png'), $name, 'image/png', null, true);
}

function galleryScenario(): array
{
    [$user, $company] = itemScenario();
    $item = Item::factory()->create(['company_id' => $company->id]);
    $storeItem = StoreItem::factory()->create(['company_id' => $company->id, 'item_id' => $item->id]);

    return [$user, $company, $storeItem];
}

test('photos are uploaded to the public disk under the publication folder', function () {
    Storage::fake('public');
    [$user, $company, $storeItem] = galleryScenario();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('store-items.images.store', ['company' => $company->id, 'id' => $storeItem->id]), [
            'images' => [photoUpload('a.png'), photoUpload('b.png')],
            'alt_text' => 'Vista frontal',
        ])
        ->assertSessionHasNoErrors();

    $images = StoreItemImage::where('store_item_id', $storeItem->id)->orderBy('order')->get();

    expect($images)->toHaveCount(2)
        ->and($images[0]->order)->toBe(1)
        ->and($images[1]->order)->toBe(2)
        ->and($images[0]->alt_text)->toBe('Vista frontal')
        ->and($images[0]->company_id)->toBe($company->id)
        ->and($images[0]->width)->toBe(2)
        ->and($images[0]->height)->toBe(2)
        ->and($images[0]->path)->toStartWith("store/{$company->id}/{$storeItem->id}/");

    Storage::disk('public')->assertExists($images[0]->path);
    Storage::disk('public')->assertExists($images[1]->path);
});

test('only jpg, png and webp files are accepted', function () {
    Storage::fake('public');
    [$user, $company, $storeItem] = galleryScenario();

    $pdf = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf');

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('store-items.images.store', ['company' => $company->id, 'id' => $storeItem->id]), [
            'images' => [$pdf],
        ])
        ->assertSessionHasErrors(['images.0']);
});

test('a publication admits at most eight active photos', function () {
    Storage::fake('public');
    [$user, $company, $storeItem] = galleryScenario();

    StoreItemImage::factory()->count(7)->create(['store_item_id' => $storeItem->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('store-items.images.store', ['company' => $company->id, 'id' => $storeItem->id]), [
            'images' => [photoUpload('a.png'), photoUpload('b.png')],
        ])
        ->assertSessionHasErrors(['images']);

    expect(StoreItemImage::where('store_item_id', $storeItem->id)->count())->toBe(7);
});

test('the gallery can be reordered', function () {
    [$user, $company, $storeItem] = galleryScenario();

    $first = StoreItemImage::factory()->create(['store_item_id' => $storeItem->id, 'order' => 0]);
    $second = StoreItemImage::factory()->create(['store_item_id' => $storeItem->id, 'order' => 1]);
    $third = StoreItemImage::factory()->create(['store_item_id' => $storeItem->id, 'order' => 2]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-items.images.reorder', ['company' => $company->id, 'id' => $storeItem->id]), [
            'order' => [$third->id, $first->id, $second->id],
        ])
        ->assertSessionHasNoErrors();

    expect($third->fresh()->order)->toBe(0)
        ->and($first->fresh()->order)->toBe(1)
        ->and($second->fresh()->order)->toBe(2);
});

test('removing a photo deactivates it instead of deleting it', function () {
    [$user, $company, $storeItem] = galleryScenario();
    $image = StoreItemImage::factory()->create(['store_item_id' => $storeItem->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-items.images.update-status', [
            'company' => $company->id,
            'id' => $storeItem->id,
            'image' => $image->id,
        ]), ['status' => 'inactive'])
        ->assertSessionHasNoErrors();

    expect(StoreItemImage::find($image->id))->not->toBeNull()
        ->and($image->fresh()->status)->toBe('inactive');
});

test('a photo of another publication cannot be touched', function () {
    [$user, $company, $storeItem] = galleryScenario();
    $otherItem = Item::factory()->create(['company_id' => $company->id]);
    $otherStoreItem = StoreItem::factory()->create(['company_id' => $company->id, 'item_id' => $otherItem->id]);
    $image = StoreItemImage::factory()->create(['store_item_id' => $otherStoreItem->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-items.images.update-status', [
            'company' => $company->id,
            'id' => $storeItem->id,
            'image' => $image->id,
        ]), ['status' => 'inactive'])
        ->assertSessionHasErrors(['image']);

    expect($image->fresh()->status)->toBe('active');
});
