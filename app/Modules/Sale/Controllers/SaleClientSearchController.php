<?php

declare(strict_types=1);

namespace App\Modules\Sale\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Client\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Búsqueda server-side de clientes activos para el wizard de venta. Devuelve un
 * máximo de 20 resultados que coincidan por nombre o código, evitando cargar
 * todos los clientes de la compañía en el formulario.
 */
class SaleClientSearchController extends Controller
{
    public function __invoke(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('sales.create') ?? false, 403);

        $term = trim((string) $request->query('q', ''));

        $clients = Client::query()
            ->where('company_id', $company)
            ->where('status', 'active')
            ->when($term !== '', function ($query) use ($term): void {
                $like = '%'.mb_strtolower($term).'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(code) LIKE ?', [$like]);
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'code']);

        return response()->json([
            'data' => $clients->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->name,
                'code' => $client->code,
            ])->all(),
        ]);
    }
}
