<?php

declare(strict_types=1);

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Commands\ClosePosSessionCommand;
use App\Modules\Pos\Requests\ClosePosSessionRequest;
use App\Modules\Pos\Services\PosSessionCloseService;
use Illuminate\Http\RedirectResponse;

class PosSessionClosePostController extends Controller
{
    public function __construct(
        private readonly PosSessionCloseService $closeService,
    ) {}

    public function __invoke(ClosePosSessionRequest $request, string $company, string $id): RedirectResponse
    {
        $session = $this->closeService->execute(
            ClosePosSessionCommand::fromRequest($request, $id, session('current_company_id'))
        );

        return redirect()
            ->route('pos-sessions.show', ['company' => $company, 'id' => $session->id])
            ->with('success', "Turno {$session->code} cerrado exitosamente. Reporte Z generado.");
    }
}
