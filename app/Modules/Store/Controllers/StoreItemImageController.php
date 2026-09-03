<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Store\Requests\UploadStoreItemImagesRequest;
use App\Modules\Store\Services\StoreItemImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * La galería va aparte del PUT de la publicación: el formulario con
 * archivos es distinto del JSON habitual.
 */
class StoreItemImageController extends Controller
{
    public function __construct(
        private readonly StoreItemImageService $imageService,
    ) {}

    public function store(UploadStoreItemImagesRequest $request, string $company, string $id): RedirectResponse
    {
        $this->imageService->upload(
            $id,
            $company,
            array_values($request->file('images', [])),
            $request->filled('alt_text') ? $request->string('alt_text')->toString() : null,
        );

        return back()->with('success', 'Fotos cargadas.');
    }

    public function reorder(Request $request, string $company, string $id): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('store-items.edit') ?? false, 403);

        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'uuid'],
        ], [
            'order.required' => 'Indica el orden de las fotos.',
        ]);

        $this->imageService->reorder($id, $company, array_values($validated['order']));

        return back()->with('success', 'Orden de la galería actualizado.');
    }

    public function updateStatus(Request $request, string $company, string $id, string $image): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('store-items.edit') ?? false, 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,inactive'],
        ], [
            'status.in' => 'El estado debe ser activo o inactivo.',
        ]);

        $this->imageService->updateStatus($id, $company, $image, $validated['status']);

        return back()->with('success', 'Foto actualizada.');
    }
}
