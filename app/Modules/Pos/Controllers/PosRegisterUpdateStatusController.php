<?php

declare(strict_types=1);

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Repositories\Contracts\PosRegisterRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PosRegisterUpdateStatusController extends Controller
{
    public function __construct(
        private readonly PosRegisterRepositoryInterface $repository,
    ) {}

    public function __invoke(Request $request, string $company, string $id): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('pos-registers.status') ?? false, 403);

        $request->validate([
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        $companyId = session('current_company_id');
        $register = $this->repository->findOrFail($id, $companyId);

        $this->repository->updateStatus($register, $request->string('status')->toString());

        return redirect()
            ->route('pos-registers.index', ['company' => $company])
            ->with('success', 'Estado de la caja actualizado exitosamente.');
    }
}
