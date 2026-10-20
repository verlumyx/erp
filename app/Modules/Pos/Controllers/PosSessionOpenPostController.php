<?php

declare(strict_types=1);

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Commands\OpenPosSessionCommand;
use App\Modules\Pos\Exceptions\PosRegisterBusyException;
use App\Modules\Pos\Exceptions\PosSessionAlreadyOpenException;
use App\Modules\Pos\Requests\OpenPosSessionRequest;
use App\Modules\Pos\Services\PosSessionOpenService;
use Illuminate\Http\RedirectResponse;

class PosSessionOpenPostController extends Controller
{
    public function __construct(
        private readonly PosSessionOpenService $openService,
    ) {}

    public function __invoke(OpenPosSessionRequest $request): RedirectResponse
    {
        try {
            $session = $this->openService->execute(
                OpenPosSessionCommand::fromRequest($request, session('current_company_id'))
            );
        } catch (PosSessionAlreadyOpenException $e) {
            return back()->withErrors(['pos_session' => $e->getMessage()]);
        } catch (PosRegisterBusyException $e) {
            return back()->withErrors(['pos_register_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('pos.terminal', ['company' => $request->route('company')])
            ->with('success', "Turno {$session->code} abierto exitosamente.");
    }
}
