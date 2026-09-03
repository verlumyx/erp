<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Configuration\Services\ConfigurationFindService;
use App\Modules\ExchangeRate\Services\Contracts\ExchangeRateResolverInterface;
use App\Modules\Store\Commands\CreateStoreOrderCommand;
use App\Modules\Store\Commands\StoreOrderLineData;
use App\Modules\Store\Exceptions\StoreDisabledException;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Models\StoreItem;
use App\Modules\Store\Models\StoreOrder;
use App\Modules\Store\Models\StoreSetting;
use App\Modules\Store\Repositories\Contracts\StoreOrderRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * El carrito llega como pedido web. La tienda no manda precios: el ERP los
 * resuelve de nuevo con la lista del cliente vinculado o la de la tienda, y
 * si un producto dejó de ser visible o quedó sin precio, la línea se
 * rechaza. No reserva existencia: eso ocurre al confirmar la orden de venta.
 */
class StoreOrderCreateService
{
    public function __construct(
        private readonly StoreOrderRepositoryInterface $repository,
        private readonly StoreCatalogService $catalog,
        private readonly ExchangeRateResolverInterface $rates,
        private readonly ConfigurationFindService $configurations,
    ) {}

    /**
     * @throws StoreDisabledException la tienda no admite pedidos
     * @throws ValidationException
     */
    public function execute(StoreSetting $settings, StoreCustomer $customer, CreateStoreOrderCommand $command): StoreOrder
    {
        if ($settings->allows_orders !== 'yes') {
            throw new StoreDisabledException('La tienda no está recibiendo pedidos por ahora.');
        }

        $lines = $this->priceLines($settings, $customer, $command->lines);
        $currency = (string) $lines[0]->currency;

        $resolved = $command->resolved(
            lines: $lines,
            clientId: $customer->client_id,
            clientAddressId: $this->resolveAddress($customer, $command),
            buyerName: $customer->name,
            buyerDocumentType: $customer->document_type,
            buyerDocumentNumber: $customer->document_number,
            buyerEmail: $customer->email,
            buyerPhone: (string) ($customer->phone ?? ''),
            currency: $currency,
            exchangeRate: $this->todayRate($settings, $currency),
        );

        $this->repository->create($resolved);

        return $this->repository->findOrFail($command->id, $command->companyId);
    }

    /**
     * @param  array<int, StoreOrderLineData>  $lines
     * @return array<int, StoreOrderLineData>
     *
     * @throws ValidationException
     */
    private function priceLines(StoreSetting $settings, StoreCustomer $customer, array $lines): array
    {
        $companyId = (string) $settings->company_id;
        $storeItems = $this->repository->visibleStoreItemsBySlug(
            $companyId,
            array_map(fn (StoreOrderLineData $line): string => $line->slug, $lines),
        );

        $itemIds = array_values(array_map(fn (StoreItem $storeItem): string => (string) $storeItem->item_id, $storeItems));
        $prices = $this->catalog->pricesFor($settings, $this->catalog->priceListFor($settings, $customer), $itemIds);
        $units = $this->repository->baseUnitIdsFor($companyId, $itemIds);

        $errors = [];
        $priced = [];
        $currency = null;

        foreach ($lines as $index => $line) {
            $storeItem = $storeItems[$line->slug] ?? null;

            if (! $storeItem instanceof StoreItem) {
                $errors["lines.{$index}.slug"] = 'El producto ya no está disponible en la tienda.';

                continue;
            }

            $price = $prices[$storeItem->item_id] ?? null;
            $unitId = $units[$storeItem->item_id] ?? null;

            if ($price === null) {
                $errors["lines.{$index}.slug"] = "«{$storeItem->title}» no tiene precio: consulta antes de pedirlo.";

                continue;
            }

            if ($unitId === null) {
                $errors["lines.{$index}.slug"] = "«{$storeItem->title}» no tiene unidad de venta.";

                continue;
            }

            $currency ??= $price['currency'];

            if ($price['currency'] !== $currency) {
                $errors["lines.{$index}.slug"] = "«{$storeItem->title}» está en otra moneda que el resto del pedido.";

                continue;
            }

            $priced[] = $line->priced(
                (string) $storeItem->item_id,
                $storeItem->id,
                $unitId,
                $price['amount'],
                $price['currency'],
            );
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $priced;
    }

    /**
     * La dirección elegida debe ser del cliente vinculado y estar activa; si
     * no hay dirección elegida, el comprador tiene que haber escrito una.
     *
     * @throws ValidationException
     */
    private function resolveAddress(StoreCustomer $customer, CreateStoreOrderCommand $command): ?string
    {
        if ($command->clientAddressId === null) {
            if ($command->deliveryAddress === null) {
                throw ValidationException::withMessages([
                    'delivery_address' => 'Indica una dirección de entrega.',
                ]);
            }

            return null;
        }

        $address = $customer->client_id === null
            ? null
            : $this->repository->findActiveClientAddress($customer->client_id, $command->clientAddressId);

        if ($address === null) {
            throw ValidationException::withMessages([
                'client_address_id' => 'La dirección elegida no es una dirección activa de tu cliente.',
            ]);
        }

        return $address->id;
    }

    /** La tasa del día se congela en el pedido; sin tasa cargada queda en 1. */
    private function todayRate(StoreSetting $settings, string $currency): float
    {
        $type = $this->configurations->execute((string) $settings->company_id)->rate_type;

        return $this->rates->tryRateFor((string) $settings->company_id, $currency, now()->toDateString(), $type) ?? 1.0;
    }
}
