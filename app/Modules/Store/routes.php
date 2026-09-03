<?php

declare(strict_types=1);

use App\Modules\Store\Controllers\Api\StoreCategoryApiController;
use App\Modules\Store\Controllers\Api\StoreCustomerApiController;
use App\Modules\Store\Controllers\Api\StoreCustomerAuthApiController;
use App\Modules\Store\Controllers\Api\StoreOrderApiController;
use App\Modules\Store\Controllers\Api\StoreProductApiController;
use App\Modules\Store\Controllers\Api\StoreSettingsApiController;
use App\Modules\Store\Controllers\StoreCustomerGetController;
use App\Modules\Store\Controllers\StoreCustomerInviteController;
use App\Modules\Store\Controllers\StoreCustomerLinkController;
use App\Modules\Store\Controllers\StoreCustomerUpdateStatusController;
use App\Modules\Store\Controllers\StoreItemGetController;
use App\Modules\Store\Controllers\StoreItemImageController;
use App\Modules\Store\Controllers\StoreItemPostController;
use App\Modules\Store\Controllers\StoreItemPutController;
use App\Modules\Store\Controllers\StoreItemUpdateStatusController;
use App\Modules\Store\Controllers\StoreOrderConvertController;
use App\Modules\Store\Controllers\StoreOrderGetController;
use App\Modules\Store\Controllers\StoreOrderRejectController;
use App\Modules\Store\Controllers\StoreSettingsGetController;
use App\Modules\Store\Controllers\StoreSettingsKeyController;
use App\Modules\Store\Controllers\StoreSettingsPutController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

/*
 * Pantallas del ERP (Inertia): Publicaciones, Compradores, Pedidos web y
 * Ajustes de tienda, bajo el grupo de menú «Tienda».
 */
Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid): void {
        Route::prefix('store-items')->group(function () use ($uuid): void {
            Route::get('/', [StoreItemGetController::class, 'index'])->name('store-items.index');
            Route::get('/create', [StoreItemGetController::class, 'create'])->name('store-items.create');
            /** Artículos publicables (vendibles, activos y sin publicación) para el select remoto. */
            Route::get('/lookup', [StoreItemGetController::class, 'lookup'])->name('store-items.lookup');
            Route::post('/', StoreItemPostController::class)->name('store-items.store');
            Route::get('/{id}', [StoreItemGetController::class, 'show'])->where('id', $uuid)->name('store-items.show');
            Route::get('/{id}/edit', [StoreItemGetController::class, 'edit'])->where('id', $uuid)->name('store-items.edit');
            Route::put('/{id}', StoreItemPutController::class)->where('id', $uuid)->name('store-items.update');
            Route::put('/{id}/status', StoreItemUpdateStatusController::class)->where('id', $uuid)->name('store-items.update-status');

            /**
             * La galería va aparte del PUT de la publicación: el formulario con
             * archivos es distinto del JSON habitual.
             */
            Route::post('/{id}/images', [StoreItemImageController::class, 'store'])->where('id', $uuid)->name('store-items.images.store');
            Route::put('/{id}/images/order', [StoreItemImageController::class, 'reorder'])->where('id', $uuid)->name('store-items.images.reorder');
            Route::put('/{id}/images/{image}/status', [StoreItemImageController::class, 'updateStatus'])->where(['id' => $uuid, 'image' => $uuid])->name('store-items.images.update-status');
        });

        Route::prefix('store-customers')->group(function () use ($uuid): void {
            Route::get('/', [StoreCustomerGetController::class, 'index'])->name('store-customers.index');
            /** El comprador vinculado a un cliente, para la tarjeta «Tienda» de la pantalla del cliente. */
            Route::get('/by-client/{client}', [StoreCustomerGetController::class, 'byClient'])->where('client', $uuid)->name('store-customers.by-client');
            Route::get('/{id}', [StoreCustomerGetController::class, 'show'])->where('id', $uuid)->name('store-customers.show');
            Route::put('/{id}/link', StoreCustomerLinkController::class)->where('id', $uuid)->name('store-customers.link');
            Route::put('/{id}/status', StoreCustomerUpdateStatusController::class)->where('id', $uuid)->name('store-customers.update-status');
        });

        /**
         * La invitación se dispara desde la pantalla del cliente, pero la ruta
         * vive aquí: `app/Modules/Client` no se toca.
         */
        Route::post('/clients/{client}/store-invitation', StoreCustomerInviteController::class)
            ->where('client', $uuid)
            ->name('store-customers.invite');

        Route::prefix('store-orders')->group(function () use ($uuid): void {
            Route::get('/', [StoreOrderGetController::class, 'index'])->name('store-orders.index');
            Route::get('/{id}', [StoreOrderGetController::class, 'show'])->where('id', $uuid)->name('store-orders.show');
            Route::put('/{id}/convert', StoreOrderConvertController::class)->where('id', $uuid)->name('store-orders.convert');
            Route::put('/{id}/reject', StoreOrderRejectController::class)->where('id', $uuid)->name('store-orders.reject');
        });

        /** Singleton: sin `{id}`, la empresa de la URL lo identifica. */
        Route::get('/store-settings', [StoreSettingsGetController::class, 'edit'])->name('store-settings.edit');
        Route::put('/store-settings', StoreSettingsPutController::class)->name('store-settings.update');
        Route::post('/store-settings/key', StoreSettingsKeyController::class)->name('store-settings.generate-key');
    });

/*
 * API pública de la tienda. Sin `auth:sanctum` en la base: la llave
 * `X-Store-Key` identifica a la empresa y `store.key` la valida. La empresa
 * no va en la URL a propósito.
 */
Route::prefix('api/store/v1')
    ->middleware(['store.key'])
    ->group(function (): void {
        Route::middleware('throttle:120,1')->group(function (): void {
            Route::get('settings', StoreSettingsApiController::class)->name('api.store.settings');
            Route::get('categories', StoreCategoryApiController::class)->name('api.store.categories');
            Route::get('products', [StoreProductApiController::class, 'index'])->name('api.store.products.index');
            Route::get('products/{slug}', [StoreProductApiController::class, 'show'])->name('api.store.products.show');
        });

        Route::prefix('customers')->middleware('throttle:20,1')->group(function (): void {
            Route::post('register', [StoreCustomerAuthApiController::class, 'register'])->name('api.store.customers.register');
            Route::post('login', [StoreCustomerAuthApiController::class, 'login'])->name('api.store.customers.login');
            Route::post('invitations/{token}', [StoreCustomerAuthApiController::class, 'acceptInvitation'])->name('api.store.customers.accept-invitation');
        });

        Route::middleware(['store.customer', 'throttle:120,1'])->group(function (): void {
            Route::get('customers/me', [StoreCustomerApiController::class, 'me'])->name('api.store.customers.me');
            Route::get('customers/orders', [StoreCustomerApiController::class, 'orders'])->name('api.store.customers.orders');
            Route::get('orders/{code}', [StoreOrderApiController::class, 'show'])->name('api.store.orders.show');
        });

        Route::post('orders', [StoreOrderApiController::class, 'store'])
            ->middleware(['store.customer', 'throttle:10,1'])
            ->name('api.store.orders.store');
    });
