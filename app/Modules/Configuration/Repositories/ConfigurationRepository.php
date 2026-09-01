<?php

declare(strict_types=1);

namespace App\Modules\Configuration\Repositories;

use App\Modules\Configuration\Commands\CreateConfigurationCommand;
use App\Modules\Configuration\Commands\UpdateConfigurationCommand;
use App\Modules\Configuration\Models\Configuration;
use App\Modules\Configuration\Repositories\Contracts\ConfigurationRepositoryInterface;

class ConfigurationRepository implements ConfigurationRepositoryInterface
{
    public function create(CreateConfigurationCommand $command): void
    {
        Configuration::create([
            'id' => $command->id,
            'company_id' => $command->companyId,
            'base_currency' => $command->baseCurrency,
            'secondary_currency' => $command->secondaryCurrency,
            'rate_type' => $command->rateType,
            'allows_rate_override' => $command->allowsRateOverride,
            'amount_decimals' => $command->amountDecimals,
            'price_decimals' => $command->priceDecimals,
            'adjustment_approval_threshold' => $command->adjustmentApprovalThreshold,
            'created_by' => $command->createdBy,
        ]);
    }

    public function findByCompany(string $companyId): ?Configuration
    {
        return Configuration::query()
            ->where('company_id', $companyId)
            ->first();
    }

    public function findOrFailByCompany(string $companyId): Configuration
    {
        return Configuration::query()
            ->where('company_id', $companyId)
            ->firstOrFail();
    }

    public function update(Configuration $model, UpdateConfigurationCommand $command): void
    {
        $model->update([
            'base_currency' => $command->baseCurrency,
            'secondary_currency' => $command->secondaryCurrency,
            'rate_type' => $command->rateType,
            'allows_rate_override' => $command->allowsRateOverride,
            'amount_decimals' => $command->amountDecimals,
            'price_decimals' => $command->priceDecimals,
            'adjustment_approval_threshold' => $command->adjustmentApprovalThreshold,
        ]);
    }
}
