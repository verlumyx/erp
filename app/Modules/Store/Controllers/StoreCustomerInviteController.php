<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Store\Commands\InviteStoreCustomerCommand;
use App\Modules\Store\Services\StoreCustomerInviteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * «Invitar a la tienda» desde la pantalla del cliente. La ruta vive en el
 * módulo Store para no tocar el módulo Client.
 */
class StoreCustomerInviteController extends Controller
{
    public function __construct(
        private readonly StoreCustomerInviteService $inviteService,
    ) {}

    public function __invoke(Request $request, string $company, string $client): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('store-customers.link') ?? false, 403);

        try {
            $customer = DB::transaction(fn () => $this->inviteService->execute(new InviteStoreCustomerCommand(
                clientId: $client,
                companyId: $company,
                invitedBy: $request->user()->id,
            )));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return back()->with('error', 'No se pudo enviar la invitación: '.$exception->getMessage());
        }

        return back()->with('success', "Invitación enviada a {$customer->email}.");
    }
}
