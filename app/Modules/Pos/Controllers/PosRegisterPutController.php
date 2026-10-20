<?php

declare(strict_types=1);

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Commands\UpdatePosRegisterCommand;
use App\Modules\Pos\Repositories\Contracts\PosRegisterRepositoryInterface;
use App\Modules\Pos\Requests\UpdatePosRegisterRequest;
use Illuminate\Http\RedirectResponse;

class PosRegisterPutController extends Controller
{
    public function __construct(
        private readonly PosRegisterRepositoryInterface $repository,
    ) {}

    public function __invoke(UpdatePosRegisterRequest $request, string $company, string $id): RedirectResponse
    {
        $companyId = session('current_company_id');
        $register = $this->repository->findOrFail($id, $companyId);

        $this->repository->update(
            $register,
            UpdatePosRegisterCommand::fromRequest($request, $id, $companyId)
        );

        return redirect()
            ->route('pos-registers.index', ['company' => $company])
            ->with('success', 'Caja registradora actualizada exitosamente.');
    }
}
