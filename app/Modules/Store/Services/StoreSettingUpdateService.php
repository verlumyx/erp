<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Store\Commands\UpdateStoreSettingsCommand;
use App\Modules\Store\Models\StoreSetting;
use App\Modules\Store\Repositories\Contracts\StoreSettingRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreSettingUpdateService
{
    /** Disco donde vive el logo; se sirve por `/storage/...`. */
    private const DISK = 'public';

    public function __construct(
        private readonly StoreSettingRepositoryInterface $repository,
        private readonly StoreSettingFindOrCreateService $findService,
    ) {}

    /**
     * El logo viaja aparte del comando porque es un archivo, no un dato del
     * formulario: se guarda en `store/{company}/logo.{ext}` y la ruta queda
     * en los ajustes. Quitarlo no borra el archivo anterior de inmediato:
     * pisarlo con el siguiente logo basta.
     */
    public function execute(
        string $companyId,
        UpdateStoreSettingsCommand $command,
        ?UploadedFile $logo = null,
        bool $removeLogo = false,
        ?string $userId = null,
    ): StoreSetting {
        $settings = $this->findService->execute($companyId, $userId);

        $this->repository->update($settings, $command);

        if ($logo instanceof UploadedFile) {
            $this->repository->writeLogoPath($settings, $this->storeLogo($companyId, $logo));
        } elseif ($removeLogo) {
            $this->repository->writeLogoPath($settings, null);
        }

        return $this->repository->findOrFailByCompany($companyId);
    }

    private function storeLogo(string $companyId, UploadedFile $logo): string
    {
        $extension = strtolower($logo->getClientOriginalExtension() ?: 'png');
        $path = "store/{$companyId}/logo.{$extension}";

        Storage::disk(self::DISK)->put($path, (string) $logo->get());

        return $path;
    }
}
