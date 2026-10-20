<?php

declare(strict_types=1);

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Commands\CreatePosRegisterCommand;
use App\Modules\Pos\Repositories\Contracts\PosRegisterRepositoryInterface;
use App\Modules\Pos\Requests\CreatePosRegisterRequest;
use Illuminate\Http\RedirectResponse;

class PosRegisterPostController extends Controller
{
    public function __construct(
        private readonly PosRegisterRepositoryInterface $repository,
    ) {}

    public function __invoke(CreatePosRegisterRequest $request): RedirectResponse
    {
        $this->repository->create(
            CreatePosRegisterCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()
            ->route('pos-registers.index', ['company' => $request->route('company')])
            ->with('success', 'Caja registradora creada exitosamente.');
    }
}
