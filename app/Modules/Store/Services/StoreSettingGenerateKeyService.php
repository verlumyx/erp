<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Store\Models\StoreSetting;
use App\Modules\Store\Repositories\Contracts\StoreSettingRepositoryInterface;
use Illuminate\Support\Str;

/**
 * Genera la llave con la que la tienda habla con el ERP.
 *
 * Solo se persiste su SHA-256: la llave en claro se devuelve una vez, para
 * mostrarla, y no vuelve a poder leerse. Generar otra invalida la anterior de
 * inmediato.
 */
class StoreSettingGenerateKeyService
{
    private const KEY_LENGTH = 40;

    public function __construct(
        private readonly StoreSettingRepositoryInterface $repository,
        private readonly StoreSettingFindOrCreateService $findService,
    ) {}

    public function execute(string $companyId, ?string $userId = null): string
    {
        $settings = $this->findService->execute($companyId, $userId);

        $plainKey = StoreSetting::KEY_PREFIX.Str::random(self::KEY_LENGTH);

        $this->repository->writeApiKeyHash($settings, hash('sha256', $plainKey));

        return $plainKey;
    }
}
