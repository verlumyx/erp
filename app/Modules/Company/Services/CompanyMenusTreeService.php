<?php

declare(strict_types=1);

namespace App\Modules\Company\Services;

use App\Modules\Menu\Services\GetActiveMenusService;

/**
 * El árbol completo de menús para la pantalla que decide qué ve una empresa:
 * sin filtrar por permisos ni por empresa, y sin el menú reservado al dueño
 * del sistema, que no se puede deshabilitar.
 */
class CompanyMenusTreeService
{
    public function __construct(
        private readonly GetActiveMenusService $getActiveMenusService,
    ) {}

    /**
     * @return array{ mainNavItems: array<mixed>, footerNavItems: array<mixed> }
     */
    public function execute(): array
    {
        $menus = $this->getActiveMenusService->execute();

        return [
            'mainNavItems' => $this->withoutSystemOwnerNodes($menus['mainNavItems']),
            'footerNavItems' => $this->withoutSystemOwnerNodes($menus['footerNavItems']),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    private function withoutSystemOwnerNodes(array $nodes): array
    {
        $result = [];

        foreach ($nodes as $node) {
            if (($node['permission'] ?? null) === 'system_owner') {
                continue;
            }

            $node['children'] = $this->withoutSystemOwnerNodes($node['children'] ?? []);
            $result[] = $node;
        }

        return $result;
    }
}
