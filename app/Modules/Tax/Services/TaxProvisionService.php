<?php

declare(strict_types=1);

namespace App\Modules\Tax\Services;

use App\Modules\Tax\Commands\CreateTaxCommand;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;
use Illuminate\Support\Str;

class TaxProvisionService
{
    /**
     * Standard taxes established by Venezuelan VAT law and Providencia SNAT/2011/00071.
     *
     * @var array<int, array{tax_type: string, name: string, percentage: string, description: string}>
     */
    public const DEFAULT_TAXES = [
        [
            'tax_type' => Tax::TYPE_EXEMPT,
            'name' => 'Exento (0%)',
            'percentage' => '0.0000',
            'description' => 'Operaciones no sujetas, exentas o exoneradas de IVA (E)',
        ],
        [
            'tax_type' => Tax::TYPE_GENERAL,
            'name' => 'IVA General (16%)',
            'percentage' => '16.0000',
            'description' => 'Alícuota impositiva general vigente (G)',
        ],
        [
            'tax_type' => Tax::TYPE_REDUCED,
            'name' => 'IVA Reducido (8%)',
            'percentage' => '8.0000',
            'description' => 'Alícuota impositiva reducida vigente (R)',
        ],
        [
            'tax_type' => Tax::TYPE_ADDITIONAL,
            'name' => 'IVA Adicional (31%)',
            'percentage' => '31.0000',
            'description' => 'Alícuota general más alícuota adicional para bienes suntuarios (A)',
        ],
    ];

    public function __construct(
        private readonly TaxRepositoryInterface $taxRepository,
    ) {}

    /**
     * Provision default Venezuelan taxes for a company if they do not already exist.
     */
    public function provisionDefaults(string $companyId, ?string $userId = null): void
    {
        foreach (self::DEFAULT_TAXES as $def) {
            $exists = Tax::query()
                ->where('company_id', $companyId)
                ->where(function ($query) use ($def) {
                    $query->where('tax_type', $def['tax_type'])
                        ->orWhere(function ($q) use ($def) {
                            $q->where('percentage', $def['percentage'])
                                ->where('name', 'like', '%' . explode(' ', $def['name'])[0] . '%');
                        });
                })
                ->exists();

            if ($exists) {
                continue;
            }

            $this->taxRepository->create(new CreateTaxCommand(
                id: Str::uuid7()->toString(),
                companyId: $companyId,
                name: $def['name'],
                percentage: $def['percentage'],
                hasWithholding: 'no',
                withholdingPercentage: '0',
                createdBy: $userId,
                description: $def['description'],
                taxType: $def['tax_type'],
            ));
        }
    }
}
