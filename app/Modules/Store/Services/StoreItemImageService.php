<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Store\Commands\StoreItemImageData;
use App\Modules\Store\Exceptions\StoreItemNotFoundException;
use App\Modules\Store\Models\StoreItem;
use App\Modules\Store\Models\StoreItemImage;
use App\Modules\Store\Repositories\Contracts\StoreItemRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Galería de la publicación.
 *
 * Al guardar se genera una sola versión reducida (lado mayor 1600 px, WebP)
 * y se guarda esa: las miniaturas las arma la tienda. Cuando el servidor no
 * tiene GD, se guarda el archivo tal cual llegó para no bloquear la carga.
 * Quitar una foto es desactivarla; máximo ocho activas por publicación.
 */
class StoreItemImageService
{
    public const MAX_ACTIVE_IMAGES = 8;

    private const MAX_SIDE = 1600;

    private const WEBP_QUALITY = 82;

    public function __construct(
        private readonly StoreItemRepositoryInterface $repository,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, StoreItemImage>
     *
     * @throws ValidationException
     */
    public function upload(string $storeItemId, string $companyId, array $files, ?string $altText = null): array
    {
        $storeItem = $this->storeItem($storeItemId, $companyId);

        $active = $storeItem->activeImages()->count();

        if ($active + count($files) > self::MAX_ACTIVE_IMAGES) {
            throw ValidationException::withMessages([
                'images' => sprintf('Una publicación admite como máximo %d fotos activas.', self::MAX_ACTIVE_IMAGES),
            ]);
        }

        $nextOrder = (int) $storeItem->images()->max('order') + 1;

        return DB::transaction(function () use ($storeItem, $files, $altText, $nextOrder): array {
            $created = [];

            foreach ($files as $file) {
                $data = $this->persistFile($storeItem, $file, $altText);

                $created[] = StoreItemImage::create([
                    'company_id' => $storeItem->company_id,
                    'store_item_id' => $storeItem->id,
                    'path' => $data->path,
                    'alt_text' => $data->altText,
                    'order' => $nextOrder++,
                    'width' => $data->width,
                    'height' => $data->height,
                    'status' => 'active',
                ]);
            }

            return $created;
        });
    }

    /**
     * Reordena la galería con la lista de ids tal como quedó en pantalla. Un
     * id que no pertenece a la publicación se ignora; las fotos que no
     * vienen conservan su sitio al final.
     *
     * @param  array<int, string>  $orderedIds
     */
    public function reorder(string $storeItemId, string $companyId, array $orderedIds): void
    {
        $storeItem = $this->storeItem($storeItemId, $companyId);

        $images = $storeItem->images()->get()->keyBy('id');

        DB::transaction(function () use ($images, $orderedIds): void {
            $position = 0;
            $seen = [];

            foreach ($orderedIds as $id) {
                if (! $images->has($id)) {
                    continue;
                }

                $images->get($id)->update(['order' => $position++]);
                $seen[] = $id;
            }

            foreach ($images as $id => $image) {
                if (! in_array($id, $seen, true)) {
                    $image->update(['order' => $position++]);
                }
            }
        });
    }

    /**
     * @throws ValidationException
     */
    public function updateStatus(string $storeItemId, string $companyId, string $imageId, string $status): StoreItemImage
    {
        $storeItem = $this->storeItem($storeItemId, $companyId);

        $image = $storeItem->images()->whereKey($imageId)->first();

        if (! $image instanceof StoreItemImage) {
            throw ValidationException::withMessages(['image' => 'La foto no pertenece a esta publicación.']);
        }

        if ($status === 'active' && $image->status !== 'active'
            && $storeItem->activeImages()->count() >= self::MAX_ACTIVE_IMAGES) {
            throw ValidationException::withMessages([
                'images' => sprintf('Una publicación admite como máximo %d fotos activas.', self::MAX_ACTIVE_IMAGES),
            ]);
        }

        $image->update(['status' => $status]);

        return $image;
    }

    private function storeItem(string $storeItemId, string $companyId): StoreItem
    {
        $storeItem = $this->repository->findById($storeItemId, $companyId);

        if (! $storeItem instanceof StoreItem) {
            throw new StoreItemNotFoundException;
        }

        return $storeItem;
    }

    /**
     * Guarda el archivo en `store/{company}/{publicación}/{uuid}.{ext}` y
     * devuelve ruta y dimensiones.
     */
    private function persistFile(StoreItem $storeItem, UploadedFile $file, ?string $altText): StoreItemImageData
    {
        $original = (string) $file->get();
        $resized = $this->resizeToWebp($original);

        $extension = $resized !== null
            ? 'webp'
            : strtolower($file->getClientOriginalExtension() ?: 'jpg');

        $path = sprintf('store/%s/%s/%s.%s', $storeItem->company_id, $storeItem->id, Str::uuid7(), $extension);

        Storage::disk(StoreItemImage::DISK)->put($path, $resized ?? $original);

        $size = @getimagesizefromstring($resized ?? $original);

        return new StoreItemImageData(
            path: $path,
            width: is_array($size) ? (int) $size[0] : null,
            height: is_array($size) ? (int) $size[1] : null,
            altText: $altText,
        );
    }

    /**
     * Versión reducida en WebP. `null` cuando no hay GD o la imagen no se
     * puede decodificar: entonces se guarda el original.
     */
    private function resizeToWebp(string $contents): ?string
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_SIDE / max($width, $height, 1));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        $ok = imagewebp($target, null, self::WEBP_QUALITY);
        $output = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($target);

        return $ok ? $output : null;
    }
}
