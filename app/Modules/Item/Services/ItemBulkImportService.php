<?php

declare(strict_types=1);

namespace App\Modules\Item\Services;

use App\Modules\Category\Models\Category;
use App\Modules\Currency\Repositories\Contracts\CurrencyRepositoryInterface;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemPrice;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\Tax\Models\Tax;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ItemBulkImportService
{
    public function __construct(
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly CurrencyRepositoryInterface $currencyRepository,
    ) {}

    /**
     * @return array{
     *     success: bool,
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     total: int,
     *     errors: array<int, string>
     * }
     */
    public function execute(string $companyId, UploadedFile $file, string $userId): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (empty($rows)) {
            return [
                'success' => false,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'total' => 0,
                'errors' => ['El archivo Excel está completamente vacío.'],
            ];
        }

        // Mapeo de cabeceras (Fila 1)
        $headerRow = array_shift($rows);
        $colMap = $this->resolveColumnMapping($headerRow);

        if (! isset($colMap['sku'])) {
            return [
                'success' => false,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'total' => 0,
                'errors' => ['No se encontró la columna requerida "SKU" en la primera fila de la plantilla.'],
            ];
        }

        // Precarga de catálogos en memoria para prevenir N+1
        $existingItems = Item::query()
            ->with(['prices', 'units'])
            ->where('company_id', $companyId)
            ->get()
            ->keyBy(fn (Item $item): string => strtoupper(trim((string) $item->sku)));

        $categories = Category::where('company_id', $companyId)->where('status', 'active')->get();
        $categoriesByName = $categories->keyBy(fn ($c) => mb_strtolower(trim($c->name)));
        $categoriesByCode = $categories->keyBy(fn ($c) => mb_strtolower(trim($c->code)));

        $units = MeasurementUnit::where('company_id', $companyId)->where('status', 'active')->get();
        $unitsByAbbr = $units->keyBy(fn ($u) => mb_strtolower(trim($u->abbreviation)));
        $unitsByName = $units->keyBy(fn ($u) => mb_strtolower(trim($u->name)));
        $defaultUnit = $units->first();

        $priceLists = PriceList::where('company_id', $companyId)->where('status', 'active')->get();
        $priceListsByName = $priceLists->keyBy(fn ($p) => mb_strtolower(trim($p->name)));
        $priceListsByCode = $priceLists->keyBy(fn ($p) => mb_strtolower(trim($p->code)));
        $defaultPriceList = $priceLists->first();

        $taxes = Tax::where('company_id', $companyId)->where('status', 'active')->get();
        $taxesByName = $taxes->keyBy(fn ($t) => mb_strtolower(trim($t->name)));
        $taxesByPercent = $taxes->keyBy(fn ($t) => (string) (float) $t->percentage);

        $activeCurrencies = $this->currencyRepository->activeCodes();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1; // Contador de filas físicas del Excel (empezando en 2 para datos)

        foreach ($rows as $row) {
            $rowNumber++;

            // Verificar si la fila está completamente vacía
            if ($this->isRowEmpty($row, $colMap)) {
                continue;
            }

            $skuRaw = $this->getCellValue($row, $colMap, 'sku');
            if ($skuRaw === null || trim($skuRaw) === '') {
                $errors[] = "Fila {$rowNumber}: El campo SKU es obligatorio.";
                $skipped++;

                continue;
            }

            $sku = trim($skuRaw);
            $skuKey = strtoupper($sku);

            try {
                if ($existingItems->has($skuKey)) {
                    /** @var Item $item */
                    $item = $existingItems->get($skuKey);

                    DB::transaction(function () use (
                        $item,
                        $row,
                        $colMap,
                        $companyId,
                        $categoriesByName,
                        $categoriesByCode,
                        $taxesByName,
                        $taxesByPercent,
                        $priceListsByName,
                        $priceListsByCode,
                        $defaultPriceList,
                        $activeCurrencies
                    ): void {
                        $this->updateItem(
                            $item,
                            $row,
                            $colMap,
                            $companyId,
                            $categoriesByName,
                            $categoriesByCode,
                            $taxesByName,
                            $taxesByPercent,
                            $priceListsByName,
                            $priceListsByCode,
                            $defaultPriceList,
                            $activeCurrencies
                        );
                    });

                    $updated++;
                } else {
                    // Crear nuevo artículo
                    $nameRaw = $this->getCellValue($row, $colMap, 'name');
                    if ($nameRaw === null || trim($nameRaw) === '') {
                        $errors[] = "Fila {$rowNumber} (SKU: {$sku}): El nombre es obligatorio para artículos nuevos.";
                        $skipped++;

                        continue;
                    }

                    // Validar unidad de medida
                    $unitStr = $this->getCellValue($row, $colMap, 'unit');
                    $baseUnitModel = null;
                    if ($unitStr !== null && trim($unitStr) !== '') {
                        $needle = mb_strtolower(trim($unitStr));
                        $baseUnitModel = $unitsByAbbr->get($needle) ?? $unitsByName->get($needle);
                        if (! $baseUnitModel) {
                            $errors[] = "Fila {$rowNumber} (SKU: {$sku}): La unidad de medida '{$unitStr}' no existe en la empresa.";
                            $skipped++;

                            continue;
                        }
                    } else {
                        $baseUnitModel = $defaultUnit;
                    }

                    if (! $baseUnitModel) {
                        $errors[] = "Fila {$rowNumber} (SKU: {$sku}): No hay unidades de medida configuradas en la empresa.";
                        $skipped++;

                        continue;
                    }

                    // Validar impuesto de venta
                    $stStr = $this->getCellValue($row, $colMap, 'sale_tax');
                    if ($stStr === null || trim($stStr) === '') {
                        $errors[] = "Fila {$rowNumber} (SKU: {$sku}): El impuesto de venta es obligatorio.";
                        $skipped++;

                        continue;
                    }
                    $stNeedle = mb_strtolower(trim($stStr));
                    $stCleanPercent = str_replace(['%', ' '], '', $stNeedle);
                    $saleTaxModel = $taxesByName->get($stNeedle) ?? $taxesByPercent->get($stCleanPercent);
                    if (! $saleTaxModel) {
                        $errors[] = "Fila {$rowNumber} (SKU: {$sku}): El impuesto de venta '{$stStr}' no existe en la empresa.";
                        $skipped++;

                        continue;
                    }

                    // Validar impuesto de compra
                    $ptStr = $this->getCellValue($row, $colMap, 'purchase_tax');
                    if ($ptStr === null || trim($ptStr) === '') {
                        $errors[] = "Fila {$rowNumber} (SKU: {$sku}): El impuesto de compra es obligatorio.";
                        $skipped++;

                        continue;
                    }
                    $ptNeedle = mb_strtolower(trim($ptStr));
                    $ptCleanPercent = str_replace(['%', ' '], '', $ptNeedle);
                    $purchaseTaxModel = $taxesByName->get($ptNeedle) ?? $taxesByPercent->get($ptCleanPercent);
                    if (! $purchaseTaxModel) {
                        $errors[] = "Fila {$rowNumber} (SKU: {$sku}): El impuesto de compra '{$ptStr}' no existe en la empresa.";
                        $skipped++;

                        continue;
                    }

                    // Validar código de barras si viene
                    $barcodeRaw = $this->getCellValue($row, $colMap, 'barcode');
                    $barcode = ($barcodeRaw !== null && trim($barcodeRaw) !== '') ? trim($barcodeRaw) : null;
                    if ($barcode !== null) {
                        $duplicateBarcode = Item::where('company_id', $companyId)->where('barcode', $barcode)->exists();
                        if ($duplicateBarcode) {
                            $errors[] = "Fila {$rowNumber} (SKU: {$sku}): El código de barras '{$barcode}' ya está en uso en otro artículo.";
                            $skipped++;

                            continue;
                        }
                    }

                    $newItem = DB::transaction(function () use (
                        $companyId,
                        $sku,
                        $nameRaw,
                        $barcode,
                        $row,
                        $colMap,
                        $userId,
                        $baseUnitModel,
                        $saleTaxModel,
                        $purchaseTaxModel,
                        $categoriesByName,
                        $categoriesByCode,
                        $priceListsByName,
                        $priceListsByCode,
                        $defaultPriceList,
                        $activeCurrencies
                    ): Item {
                        return $this->createItem(
                            $companyId,
                            $sku,
                            trim($nameRaw),
                            $barcode,
                            $row,
                            $colMap,
                            $userId,
                            $baseUnitModel,
                            $saleTaxModel->id,
                            $purchaseTaxModel->id,
                            $categoriesByName,
                            $categoriesByCode,
                            $priceListsByName,
                            $priceListsByCode,
                            $defaultPriceList,
                            $activeCurrencies
                        );
                    });

                    $existingItems->put($skuKey, $newItem);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Fila {$rowNumber} (SKU: {$sku}): ".$e->getMessage();
                $skipped++;
            }
        }

        return [
            'success' => true,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => $created + $updated + $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $colMap
     */
    private function updateItem(
        Item $item,
        array $row,
        array $colMap,
        string $companyId,
        $categoriesByName,
        $categoriesByCode,
        $taxesByName,
        $taxesByPercent,
        $priceListsByName,
        $priceListsByCode,
        ?PriceList $defaultPriceList,
        array $activeCurrencies
    ): void {
        $updates = [];

        $name = $this->getCellValue($row, $colMap, 'name');
        if ($name !== null && trim($name) !== '') {
            $updates['name'] = trim($name);
        }

        $barcode = $this->getCellValue($row, $colMap, 'barcode');
        if ($barcode !== null) {
            $barcodeVal = trim($barcode);
            if ($barcodeVal !== '' && $barcodeVal !== $item->barcode) {
                $exists = Item::where('company_id', $companyId)
                    ->where('barcode', $barcodeVal)
                    ->where('id', '!=', $item->id)
                    ->exists();
                if (! $exists) {
                    $updates['barcode'] = $barcodeVal;
                }
            } elseif ($barcodeVal === '') {
                $updates['barcode'] = null;
            }
        }

        $desc = $this->getCellValue($row, $colMap, 'description');
        if ($desc !== null) {
            $updates['description'] = trim($desc) ?: null;
        }

        $type = $this->getCellValue($row, $colMap, 'type');
        if ($type !== null && in_array(trim($type), Item::TYPES, true)) {
            $updates['type'] = trim($type);
        }

        $costMethod = $this->getCellValue($row, $colMap, 'cost_method');
        if ($costMethod !== null && in_array(trim($costMethod), Item::COST_METHODS, true)) {
            $updates['cost_method'] = trim($costMethod);
        }

        $standardCost = $this->getCellValue($row, $colMap, 'standard_cost');
        if ($standardCost !== null && is_numeric($standardCost) && (float) $standardCost >= 0) {
            $updates['standard_cost'] = (string) (float) $standardCost;
        }

        $minPrice = $this->getCellValue($row, $colMap, 'min_price');
        if ($minPrice !== null && is_numeric($minPrice) && (float) $minPrice >= 0) {
            $updates['min_price'] = (string) (float) $minPrice;
        }

        $catStr = $this->getCellValue($row, $colMap, 'category');
        if ($catStr !== null && trim($catStr) !== '') {
            $needle = mb_strtolower(trim($catStr));
            $cat = $categoriesByName->get($needle) ?? $categoriesByCode->get($needle);
            if ($cat) {
                $updates['category_id'] = $cat->id;
            }
        }

        $saleTaxStr = $this->getCellValue($row, $colMap, 'sale_tax');
        if ($saleTaxStr !== null && trim($saleTaxStr) !== '') {
            $needle = mb_strtolower(trim($saleTaxStr));
            $cleanPercent = str_replace(['%', ' '], '', $needle);
            $tax = $taxesByName->get($needle) ?? $taxesByPercent->get($cleanPercent);
            if (! $tax) {
                throw new \InvalidArgumentException("El impuesto de venta '{$saleTaxStr}' no existe en la empresa.");
            }
            $updates['sale_tax_id'] = $tax->id;
        }

        $purchaseTaxStr = $this->getCellValue($row, $colMap, 'purchase_tax');
        if ($purchaseTaxStr !== null && trim($purchaseTaxStr) !== '') {
            $needle = mb_strtolower(trim($purchaseTaxStr));
            $cleanPercent = str_replace(['%', ' '], '', $needle);
            $tax = $taxesByName->get($needle) ?? $taxesByPercent->get($cleanPercent);
            if (! $tax) {
                throw new \InvalidArgumentException("El impuesto de compra '{$purchaseTaxStr}' no existe en la empresa.");
            }
            $updates['purchase_tax_id'] = $tax->id;
        }

        $notes = $this->getCellValue($row, $colMap, 'notes');
        if ($notes !== null) {
            $updates['notes'] = trim($notes) ?: null;
        }

        if (! empty($updates)) {
            $item->update($updates);
        }

        // Actualización de precio si viene en la fila
        $priceRaw = $this->getCellValue($row, $colMap, 'price');
        if ($priceRaw !== null && is_numeric($priceRaw) && (float) $priceRaw >= 0) {
            $priceVal = (float) $priceRaw;

            // Determinar lista de precio objetivo
            $plStr = $this->getCellValue($row, $colMap, 'price_list');
            $targetPriceList = null;
            if ($plStr !== null && trim($plStr) !== '') {
                $needle = mb_strtolower(trim($plStr));
                $targetPriceList = $priceListsByName->get($needle) ?? $priceListsByCode->get($needle);
            }
            $targetPriceList = $targetPriceList ?? $defaultPriceList;

            // Determinar moneda
            $currencyRaw = $this->getCellValue($row, $colMap, 'currency');
            $currency = 'USD';
            if ($currencyRaw !== null && trim($currencyRaw) !== '') {
                $currCandidate = strtoupper(trim($currencyRaw));
                if (in_array($currCandidate, $activeCurrencies, true)) {
                    $currency = $currCandidate;
                }
            } else {
                // Conservar la moneda anterior si existía
                $existingPrice = $item->prices->firstWhere('price_list_id', $targetPriceList?->id);
                if ($existingPrice) {
                    $currency = $existingPrice->currency;
                }
            }

            if ($targetPriceList) {
                $itemPrice = ItemPrice::where('company_id', $companyId)
                    ->where('item_id', $item->id)
                    ->where('price_list_id', $targetPriceList->id)
                    ->first();

                if ($itemPrice) {
                    $itemPrice->update([
                        'price' => $priceVal,
                        'currency' => $currency,
                        'status' => 'active',
                    ]);
                } else {
                    ItemPrice::create([
                        'id' => Str::uuid7()->toString(),
                        'company_id' => $companyId,
                        'item_id' => $item->id,
                        'price_list_id' => $targetPriceList->id,
                        'price' => $priceVal,
                        'currency' => $currency,
                        'status' => 'active',
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $colMap
     */
    private function createItem(
        string $companyId,
        string $sku,
        string $name,
        ?string $barcode,
        array $row,
        array $colMap,
        string $userId,
        MeasurementUnit $baseUnitModel,
        string $saleTaxId,
        string $purchaseTaxId,
        $categoriesByName,
        $categoriesByCode,
        $priceListsByName,
        $priceListsByCode,
        ?PriceList $defaultPriceList,
        array $activeCurrencies
    ): Item {
        $itemId = Str::uuid7()->toString();
        $code = $this->itemRepository->generateNextCode($companyId);

        $type = 'inventoried';
        $typeRaw = $this->getCellValue($row, $colMap, 'type');
        if ($typeRaw !== null && in_array(trim($typeRaw), Item::TYPES, true)) {
            $type = trim($typeRaw);
        }

        $costMethod = 'average';
        $costMethodRaw = $this->getCellValue($row, $colMap, 'cost_method');
        if ($costMethodRaw !== null && in_array(trim($costMethodRaw), Item::COST_METHODS, true)) {
            $costMethod = trim($costMethodRaw);
        }

        $standardCost = '0';
        $scRaw = $this->getCellValue($row, $colMap, 'standard_cost');
        if ($scRaw !== null && is_numeric($scRaw) && (float) $scRaw >= 0) {
            $standardCost = (string) (float) $scRaw;
        }

        $minPrice = '0';
        $mpRaw = $this->getCellValue($row, $colMap, 'min_price');
        if ($mpRaw !== null && is_numeric($mpRaw) && (float) $mpRaw >= 0) {
            $minPrice = (string) (float) $mpRaw;
        }

        $categoryId = null;
        $catStr = $this->getCellValue($row, $colMap, 'category');
        if ($catStr !== null && trim($catStr) !== '') {
            $needle = mb_strtolower(trim($catStr));
            $cat = $categoriesByName->get($needle) ?? $categoriesByCode->get($needle);
            $categoryId = $cat?->id;
        }

        $description = null;
        $descRaw = $this->getCellValue($row, $colMap, 'description');
        if ($descRaw !== null && trim($descRaw) !== '') {
            $description = trim($descRaw);
        }

        $notes = null;
        $notesRaw = $this->getCellValue($row, $colMap, 'notes');
        if ($notesRaw !== null && trim($notesRaw) !== '') {
            $notes = trim($notesRaw);
        }

        $item = Item::create([
            'id' => $itemId,
            'company_id' => $companyId,
            'code' => $code,
            'sku' => $sku,
            'barcode' => $barcode,
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'category_id' => $categoryId,
            'sale_tax_id' => $saleTaxId,
            'purchase_tax_id' => $purchaseTaxId,
            'cost_method' => $costMethod,
            'standard_cost' => $standardCost,
            'average_cost' => 0,
            'min_price' => $minPrice,
            'is_purchasable' => 'yes',
            'is_sellable' => 'yes',
            'min_stock' => 0,
            'max_stock' => 0,
            'reorder_quantity' => 0,
            'weight' => 0,
            'volume' => 0,
            'notes' => $notes,
            'status' => 'active',
            'created_by' => $userId,
        ]);

        // Crear unidad base
        ItemUnit::create([
            'id' => Str::uuid7()->toString(),
            'company_id' => $companyId,
            'item_id' => $item->id,
            'measurement_unit_id' => $baseUnitModel->id,
            'is_base' => 'yes',
            'conversion_factor' => 1,
            'status' => 'active',
        ]);

        // Crear precio si se especificó
        $priceRaw = $this->getCellValue($row, $colMap, 'price');
        $plStr = $this->getCellValue($row, $colMap, 'price_list');
        $targetPriceList = null;
        if ($plStr !== null && trim($plStr) !== '') {
            $needle = mb_strtolower(trim($plStr));
            $targetPriceList = $priceListsByName->get($needle) ?? $priceListsByCode->get($needle);
        }
        $targetPriceList = $targetPriceList ?? $defaultPriceList;

        if ($targetPriceList) {
            $priceVal = 0;
            if ($priceRaw !== null && is_numeric($priceRaw) && (float) $priceRaw >= 0) {
                $priceVal = (float) $priceRaw;
            }

            $currency = 'USD';
            $currRaw = $this->getCellValue($row, $colMap, 'currency');
            if ($currRaw !== null && in_array(strtoupper(trim($currRaw)), $activeCurrencies, true)) {
                $currency = strtoupper(trim($currRaw));
            }

            ItemPrice::create([
                'id' => Str::uuid7()->toString(),
                'company_id' => $companyId,
                'item_id' => $item->id,
                'price_list_id' => $targetPriceList->id,
                'price' => $priceVal,
                'currency' => $currency,
                'status' => 'active',
            ]);
        }

        return $item;
    }

    /**
     * @param  array<string, mixed>  $headerRow
     * @return array<string, string>
     */
    private function resolveColumnMapping(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $colLetter => $rawHeader) {
            if ($rawHeader === null || trim((string) $rawHeader) === '') {
                continue;
            }

            $normalized = $this->normalizeString((string) $rawHeader);

            if (in_array($normalized, ['sku'], true)) {
                $map['sku'] = (string) $colLetter;
            } elseif (in_array($normalized, ['nombre', 'name', 'articulo'], true)) {
                $map['name'] = (string) $colLetter;
            } elseif (in_array($normalized, ['codigo', 'codigoerp', 'codigointerno'], true)) {
                $map['code'] = (string) $colLetter;
            } elseif (in_array($normalized, ['codigodebarras', 'codigobarras', 'barcode'], true)) {
                $map['barcode'] = (string) $colLetter;
            } elseif (in_array($normalized, ['precio', 'price', 'preciodeventa'], true)) {
                $map['price'] = (string) $colLetter;
            } elseif (in_array($normalized, ['moneda', 'currency'], true)) {
                $map['currency'] = (string) $colLetter;
            } elseif (in_array($normalized, ['listadeprecio', 'listaprecio', 'listadeprecios', 'pricelist'], true)) {
                $map['price_list'] = (string) $colLetter;
            } elseif (in_array($normalized, ['costoestandar', 'costo', 'standardcost'], true)) {
                $map['standard_cost'] = (string) $colLetter;
            } elseif (in_array($normalized, ['preciominimo', 'minprice'], true)) {
                $map['min_price'] = (string) $colLetter;
            } elseif (in_array($normalized, ['tipo', 'type'], true)) {
                $map['type'] = (string) $colLetter;
            } elseif (in_array($normalized, ['categoria', 'category'], true)) {
                $map['category'] = (string) $colLetter;
            } elseif (in_array($normalized, ['unidadbase', 'unidad', 'unidaddemedida', 'unit'], true)) {
                $map['unit'] = (string) $colLetter;
            } elseif (in_array($normalized, ['impuestoventa', 'saletax', 'impuestodeventa'], true)) {
                $map['sale_tax'] = (string) $colLetter;
            } elseif (in_array($normalized, ['impuestocompra', 'purchasetax', 'impuestodecompra'], true)) {
                $map['purchase_tax'] = (string) $colLetter;
            } elseif (in_array($normalized, ['metododecosto', 'metodocosto', 'costmethod'], true)) {
                $map['cost_method'] = (string) $colLetter;
            } elseif (in_array($normalized, ['descripcion', 'description'], true)) {
                $map['description'] = (string) $colLetter;
            } elseif (in_array($normalized, ['notas', 'notes'], true)) {
                $map['notes'] = (string) $colLetter;
            }
        }

        return $map;
    }

    private function normalizeString(string $val): string
    {
        $val = mb_strtolower(trim($val));
        $val = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', '*', '(', ')', '-', '_', ' '],
            ['a', 'e', 'i', 'o', 'u', 'n', '', '', '', '', '', ''],
            $val
        );

        return preg_replace('/[^a-z0-9]/', '', $val) ?? '';
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $colMap
     */
    private function getCellValue(array $row, array $colMap, string $field): ?string
    {
        if (! isset($colMap[$field])) {
            return null;
        }

        $letter = $colMap[$field];
        $val = $row[$letter] ?? null;

        if ($val === null) {
            return null;
        }

        return (string) $val;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $colMap
     */
    private function isRowEmpty(array $row, array $colMap): bool
    {
        foreach ($colMap as $letter) {
            $val = $row[$letter] ?? null;
            if ($val !== null && trim((string) $val) !== '') {
                return false;
            }
        }

        return true;
    }
}
